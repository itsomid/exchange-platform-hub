<?php

namespace App\Services\Exchanges\Asset\Binance;

use App\Enums\SpotStatusEnum;
use App\Exceptions\Exchange\RefExchangeRequestException;
use App\Exceptions\Exchange\RefExchangeWithdrawalException;
use App\Models\Currency;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawResponseDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssetBinance implements AssetInterface
{
    public function getBalance(): array
    {
        try {
            $response = BinanceRequest::sendRequest('GET', '/api/v3/account');
        } catch (\Throwable $exception) {
            report($exception);
            throw new RefExchangeRequestException("Can't Resolve " . config('exchanges.binance.base_url'));
        }

        $json = $response->json();

        if (!$response->ok() || !isset($json['balances']) || !is_array($json['balances'])) {
            $errorCode = $json['code'] ?? null;
            $errorMsg = $json['msg'] ?? ($json['message'] ?? $response->body());
            $hint = $this->resolveBinanceErrorHint($errorCode, (string) $errorMsg);

            Log::channel('ref-exchange')->error('Binance getBalance failed', [
                'http_status' => $response->status(),
                'error_code' => $errorCode,
                'error_msg' => $errorMsg,
                'hint' => $hint,
                'api_key_configured' => filled(config('exchanges.binance.api_key')),
                'api_key_prefix' => substr((string) config('exchanges.binance.api_key'), 0, 8),
                'base_url' => config('exchanges.binance.base_url'),
                'response_body' => $response->body(),
            ]);

            throw new RefExchangeRequestException(trim(sprintf(
                'Binance getBalance failed [%s]: %s%s',
                $errorCode !== null ? "code={$errorCode}" : "http={$response->status()}",
                $errorMsg,
                $hint ? " | Hint: {$hint}" : ''
            )));
        }

        $balances = array_filter($json['balances'], function ($item) {
            $free = (float) ($item['free'] ?? 0);
            $locked = (float) ($item['locked'] ?? 0);

            return $free > 0 || $locked > 0;
        });

        return array_map(function ($item) {
            return resolve(BalanceResponseDTO::class)
                ->setCcy($item['asset'] ?? '')
                ->setFrozen($item['locked'] ?? '0')
                ->setAvailable($item['free'] ?? '0');
        }, array_values($balances));
    }

    public function placeOrder(BuyDTORequest $request): BuyDTOResponse
    {
        $symbol = BinanceOrderFormatter::normalizeSymbol($request->getMarket());
        $orderType = strtoupper($request->getOrderType());
        $side = strtoupper($request->getSide());

        try {
            $filters = $this->symbolFilters($symbol);
            $currency = Currency::where('symbol', $request->getCurrency())->first();
            $quantity = BinanceOrderFormatter::formatQuantity(
                $request->getQuantity(),
                BinanceOrderFormatter::stepFor($orderType, $filters),
                $currency?->amount_precision ?? 8
            );

            $minQty = BinanceOrderFormatter::minQtyFor($orderType, $filters) ?? '0';
            if (bccomp($quantity, '0', 18) !== 1 || bccomp($quantity, $minQty, 18) === -1) {
                return $this->failedOrder(SpotStatusEnum::AmountTooSmall, -1013, 'مقدار سفارش کمتر از حداقل مجاز بایننس است.');
            }

            $params = [
                'symbol' => $symbol,
                'side' => $side,
                'type' => $orderType,
                'quantity' => $quantity,
                'newOrderRespType' => 'FULL',
            ];

            if ($orderType === 'LIMIT') {
                $price = $request->getPrice();
                if (empty($price)) {
                    return $this->failedOrder(SpotStatusEnum::BuyOrderFailed, 0, 'Price is required for LIMIT orders');
                }

                $params['price'] = BinanceOrderFormatter::formatQuantity($price, $filters['tickSize'] ?? null);
                $params['timeInForce'] = 'GTC';
            }

            $response = BinanceRequest::sendRequest('POST', '/api/v3/order', $params);
        } catch (\Throwable $exception) {
            report($exception);

            // Keep only the cURL reason; the rest of the message is the signed request URL.
            return $this->failedOrder(SpotStatusEnum::ConnectionLosses, 0, Str::before($exception->getMessage(), ' (see '));
        }

        $json = $response->json();
        if (! is_array($json)) {
            $json = [];
        }

        if (! $response->ok() || (isset($json['code']) && (int) $json['code'] !== 0)) {
            $errorCode = (int) ($json['code'] ?? 0);
            $errorMsg = (string) ($json['msg'] ?? ($json['message'] ?? $response->body()));

            Log::channel('ref-exchange')->error('Binance placeOrder failed', [
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'response' => $json ?: $response->body(),
            ]);

            return $this->failedOrder(BinanceOrderFormatter::mapError($errorCode, $errorMsg), $errorCode, $errorMsg);
        }

        // A MARKET order that finds no liquidity comes back EXPIRED with nothing executed.
        $filledAmount = BinanceOrderFormatter::plainDecimal((string) ($json['executedQty'] ?? '0'));
        if (bccomp($filledAmount, '0', 18) !== 1) {
            Log::channel('ref-exchange')->error('Binance order returned without execution', ['response' => $json]);

            return $this->failedOrder(SpotStatusEnum::BuyOrderFailed, 0, 'سفارش در بایننس اجرا نشد.');
        }

        $filledValue = BinanceOrderFormatter::plainDecimal((string) ($json['cummulativeQuoteQty'] ?? '0'));
        $baseAsset = strtoupper($request->getCurrency());
        $quoteAsset = substr($symbol, strlen($baseAsset));
        $commission = BinanceOrderFormatter::commission(
            is_array($json['fills'] ?? null) ? $json['fills'] : [],
            $side === 'BUY' ? [$baseAsset, $quoteAsset] : [$quoteAsset, $baseAsset]
        );

        return resolve(BuyDTOResponse::class)
            ->setIsDone(true)
            ->setErrorCode(0)
            ->setSpotStatus(SpotStatusEnum::BuyOrderSubmitted)
            ->setOrderId((string) ($json['orderId'] ?? ''))
            ->setMarket($json['symbol'] ?? $symbol)
            ->setCurrencySymbol($request->getCurrency())
            ->setSide(strtolower($json['side'] ?? $side))
            ->setAmount(BinanceOrderFormatter::plainDecimal((string) ($json['origQty'] ?? $quantity)))
            ->setPrice(bcdiv($filledValue, $filledAmount, 8))
            ->setDiscountFee($commission['amount'])
            ->setFeeCurrency($commission['currency'])
            ->setFilledAmount($filledAmount)
            ->setFilledValue($filledValue)
            ->setCreatedAt(
                isset($json['transactTime'])
                    ? Carbon::createFromTimestampMs($json['transactTime'])
                    : Carbon::now()
            )
            ->setResponseBody($response->body());
    }

    /**
     * @return array<string, string> LOT_SIZE / MARKET_LOT_SIZE / PRICE_FILTER values for the symbol
     */
    private function symbolFilters(string $symbol): array
    {
        return Cache::remember('binance:symbol-filters:' . $symbol, now()->addHour(), function () use ($symbol) {
            $response = Http::timeout(10)->get(config('exchanges.binance.base_url') . '/api/v3/exchangeInfo', [
                'symbol' => $symbol,
            ]);

            if (! $response->ok()) {
                throw new \RuntimeException("Binance exchangeInfo failed for {$symbol}: " . $response->body());
            }

            $filters = [];
            foreach ($response->json('symbols.0.filters') ?? [] as $filter) {
                switch ($filter['filterType'] ?? null) {
                    case 'LOT_SIZE':
                        $filters['stepSize'] = (string) $filter['stepSize'];
                        $filters['minQty'] = (string) $filter['minQty'];
                        break;
                    case 'MARKET_LOT_SIZE':
                        $filters['marketStepSize'] = (string) $filter['stepSize'];
                        $filters['marketMinQty'] = (string) $filter['minQty'];
                        break;
                    case 'PRICE_FILTER':
                        $filters['tickSize'] = (string) $filter['tickSize'];
                        break;
                }
            }

            return $filters;
        });
    }

    private function failedOrder(SpotStatusEnum $status, int $errorCode, string $errorMessage): BuyDTOResponse
    {
        return resolve(BuyDTOResponse::class)
            ->setSpotStatus($status)
            ->setErrorCode($errorCode)
            ->setErrorMessage($errorMessage)
            ->setIsDone(false);
    }

    public function withdraw(WithdrawRequestDTO $requestDTO): WithdrawResponseDTO
    {
        $params = [
            'coin' => $requestDTO->getCurrency(),
            'address' => $requestDTO->getAddress(),
            'amount' => (string) $requestDTO->getAmount(),
        ];

        if ($requestDTO->getChain()) {
            $params['network'] = $this->mapChainToBinanceNetwork($requestDTO->getChain());
        }

        try {
            $response = BinanceRequest::sendRequest('POST', '/sapi/v1/capital/withdraw/apply', $params);
        } catch (\Throwable $exception) {
            report($exception);
            throw new RefExchangeRequestException("Can't Resolve " . config('exchanges.binance.base_url'));
        }

        $json = $response->json();

        if (!$response->ok() || !isset($json['id'])) {
            $errorCode = $json['code'] ?? null;
            $errorMsg = $json['msg'] ?? ($json['message'] ?? $response->body());
            $hint = $this->resolveBinanceErrorHint($errorCode, $errorMsg);

            Log::channel('ref-exchange')->error('Binance withdraw failed', [
                'http_status' => $response->status(),
                'error_code' => $errorCode,
                'error_msg' => $errorMsg,
                'hint' => $hint,
                'endpoint' => '/sapi/v1/capital/withdraw/apply',
                'coin' => $params['coin'] ?? null,
                'network' => $params['network'] ?? null,
                'amount' => $params['amount'] ?? null,
                'address' => $this->maskAddress($params['address'] ?? ''),
                'api_key_configured' => filled(config('exchanges.binance.api_key')),
                'api_key_prefix' => substr((string) config('exchanges.binance.api_key'), 0, 8),
                'base_url' => config('exchanges.binance.base_url'),
                'response_body' => $response->body(),
                'response_json' => $json,
            ]);

            $exceptionMessage = trim(sprintf(
                'Binance withdraw failed [%s]: %s%s',
                $errorCode !== null ? "code={$errorCode}" : "http={$response->status()}",
                $errorMsg,
                $hint ? " | Hint: {$hint}" : ''
            ));

            throw new RefExchangeWithdrawalException($exceptionMessage);
        }

        $withdrawId = $json['id'];
        $actualFee = '0';
        $currencyFee = $requestDTO->getCurrency();
        $status = 'processing';
        $createdAt = (int) round(microtime(true) * 1000);

        sleep(2);

        $withdrawalDetails = $this->getWithdrawalDetails($withdrawId, $requestDTO->getCurrency());

        if ($withdrawalDetails) {
            $actualFee = (string) ($withdrawalDetails['transactionFee'] ?? '0');
            $status = $this->mapBinanceStatusToString((int) ($withdrawalDetails['status'] ?? 4));
            if (!empty($withdrawalDetails['applyTime'])) {
                $parsed = strtotime($withdrawalDetails['applyTime']);
                if ($parsed !== false) {
                    $createdAt = $parsed * 1000;
                }
            }
        }

        return resolve(WithdrawResponseDTO::class)
            ->setWithdrawId($withdrawId)
            ->setCreatedAt($createdAt)
            ->setCurrency($requestDTO->getCurrency())
            ->setChain($requestDTO->getChain() ?? '')
            ->setAmount($requestDTO->getAmount())
            ->setActualAmount($requestDTO->getAmount())
            ->setWithdrawMethod(WithdrawMethodEnum::ON_CHAIN->value ?? '')
            ->setAddress($requestDTO->getAddress())
            ->setConfirmationCount(0)
            ->setExploreAddress('')
            ->setStatus($status)
            ->setFee($actualFee)
            ->setCurrencyFee($currencyFee);
    }

    private function getWithdrawalDetails(string $withdrawId, string $coin): ?array
    {
        try {
            $response = BinanceRequest::sendRequest('GET', '/sapi/v1/capital/withdraw/history', [
                'coin' => $coin,
                'limit' => 10,
            ]);

            if (!$response->successful()) {
                Log::channel('ref-exchange')->warning('Failed to fetch Binance withdrawal history', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $history = $response->json();

            if (!is_array($history)) {
                return null;
            }

            foreach ($history as $withdrawal) {
                if (isset($withdrawal['id']) && (string) $withdrawal['id'] === (string) $withdrawId) {
                    return $withdrawal;
                }
            }

            Log::channel('ref-exchange')->info('Binance withdrawal not found in history yet', [
                'withdraw_id' => $withdrawId,
                'coin' => $coin,
            ]);
        } catch (\Throwable $e) {
            Log::channel('ref-exchange')->error('Error fetching Binance withdrawal details', [
                'error' => $e->getMessage(),
                'withdraw_id' => $withdrawId,
            ]);
        }

        return null;
    }

    private function mapBinanceStatusToString(int $status): string
    {
        return match ($status) {
            0 => 'email_sent',
            1 => 'cancel',
            2 => 'awaiting_approval',
            3 => 'rejected',
            4 => 'processing',
            5 => 'failed',
            6 => 'success',
            default => 'unknown',
        };
    }

    private function resolveBinanceErrorHint(mixed $errorCode, string $errorMsg): ?string
    {
        $code = is_numeric($errorCode) ? (int) $errorCode : null;
        $normalizedMsg = strtolower($errorMsg);

        if ($code === -1002 || str_contains($normalizedMsg, 'not authorized')) {
            return 'API key lacks Enable Withdrawals, or request IP is not in the API key whitelist. Check Binance API Management.';
        }

        if ($code === -2015) {
            return 'Invalid API-key, IP, or permissions for action.';
        }

        if ($code === -1021) {
            return 'Timestamp outside recvWindow — check server clock sync.';
        }

        if ($code === -1022) {
            return 'Invalid signature — check EXCHANGES_BINANCE_SECRET_KEY.';
        }

        if ($code === -4026 || str_contains($normalizedMsg, 'insufficient')) {
            return 'Insufficient balance for this withdrawal.';
        }

        return null;
    }

    private function maskAddress(string $address): string
    {
        if ($address === '') {
            return '';
        }

        if (strlen($address) <= 10) {
            return str_repeat('*', strlen($address));
        }

        return substr($address, 0, 6) . '...' . substr($address, -4);
    }

    private function mapChainToBinanceNetwork(string $chain): string
    {
        $chainMapping = [
            'ERC20' => 'ETH',
            'BEP20' => 'BSC',
            'BSC' => 'BSC',
            'TRC20' => 'TRX',
            'BTC' => 'BTC',
            'ETH' => 'ETH',
            'TRX' => 'TRX',
            'DOGE' => 'DOGE',
            'SOL' => 'SOL',
            'POLYGON' => 'MATIC',
            'ARBITRUM' => 'ARBITRUM',
            'OPTIMISM' => 'OPTIMISM',
            'LTC' => 'LTC',
            'AVALANCHE' => 'AVAX',
            'AVAX' => 'AVAX',
            'FTM' => 'FTM',
            'ARB' => 'ARBITRUM',
            'OP' => 'OPTIMISM',
            'BASE' => 'BASE',
            'LINEA' => 'LINEA',
            'SONIC' => 'SONIC',
            'DASH' => 'DASH',
        ];

        return $chainMapping[$chain] ?? $chain;
    }
}
