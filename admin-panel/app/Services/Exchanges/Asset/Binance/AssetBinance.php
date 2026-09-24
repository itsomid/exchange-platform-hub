<?php

namespace App\Services\Exchanges\Asset\Binance;

use App\Enums\SpotStatusEnum;
use App\Exceptions\Exchange\CantResolveCoinexException;
use App\Exceptions\Exchange\CoinexHasProblemException;
use App\Models\Currency;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawResponseDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AssetBinance implements AssetInterface
{
    public function getBalance(): array
    {
        try {
            $response = BinanceRequest::sendRequest('GET', '/api/v3/account');
        } catch (\Throwable $exception) {
            report($exception);
            throw new CantResolveCoinexException("Can't Resolve " . config('exchanges.binance.base_url'));
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

            throw new CoinexHasProblemException(trim(sprintf(
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
        try {
            $symbol = $request->getMarket();
            $orderType = strtoupper($request->getOrderType());
            $side = strtoupper($request->getSide());

            $currency = Currency::where('symbol', $request->getCurrency())->first();
            $amountPrecision = $currency?->amount_precision;

            $params = [
                'symbol' => $symbol,
                'side' => $side,
                'type' => $orderType,
                'quantity' => formatNumberTrimZeros($request->getQuantity(), $amountPrecision ?? 8),
            ];

            if ($orderType === 'LIMIT') {
                $price = $request->getPrice();
                if (empty($price)) {
                    return resolve(BuyDTOResponse::class)
                        ->setSpotStatus(SpotStatusEnum::BuyOrderFailed)
                        ->setErrorCode(0)
                        ->setErrorMessage('Price is required for LIMIT orders')
                        ->setIsDone(false);
                }

                $params['price'] = (string) $price;
                $params['timeInForce'] = 'GTC';
            }

            $response = BinanceRequest::sendRequest('POST', '/api/v3/order', $params);
        } catch (\Throwable $exception) {
            report($exception);

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::ConnectionLosses)
                ->setErrorCode(0)
                ->setIsDone(false);
        }

        $json = $response->json();

        if (!$response->ok() || (isset($json['code']) && $json['code'] !== 0)) {
            $errorCode = $json['code'] ?? 0;
            $errorMsg = $json['msg'] ?? ($json['message'] ?? $response->body());

            Log::channel('ref-exchange')->error('Binance placeOrder failed', [
                'response' => $json,
            ]);

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::BuyOrderFailed)
                ->setErrorCode((int) $errorCode)
                ->setErrorMessage($errorMsg)
                ->setIsDone(false);
        }

        $filledAmount = $json['executedQty'] ?? ($json['origQty'] ?? '0');
        $filledValue = $json['cummulativeQuoteQty'] ?? '0';
        $price = $json['price'] ?? '0';

        if ((float) $price <= 0 && (float) $filledAmount > 0 && (float) $filledValue > 0) {
            $price = bcdiv((string) $filledValue, (string) $filledAmount, 8);
        }

        $commission = '0';
        if (!empty($json['fills']) && is_array($json['fills'])) {
            foreach ($json['fills'] as $fill) {
                $commission = bcadd($commission, (string) ($fill['commission'] ?? '0'), 8);
            }
        }

        return resolve(BuyDTOResponse::class)
            ->setIsDone(true)
            ->setErrorCode(0)
            ->setSpotStatus(SpotStatusEnum::BuyOrderSubmitted)
            ->setOrderId($json['orderId'] ?? null)
            ->setMarket($json['symbol'] ?? $request->getMarket())
            ->setCurrencySymbol($request->getCurrency())
            ->setSide($json['side'] ?? $side)
            ->setAmount($json['origQty'] ?? $request->getQuantity())
            ->setPrice($price)
            ->setDiscountFee($commission)
            ->setFilledAmount($filledAmount)
            ->setFilledValue($filledValue)
            ->setCreatedAt(
                isset($json['transactTime'])
                    ? Carbon::createFromTimestampMs($json['transactTime'])
                    : Carbon::now()
            )
            ->setResponseBody($response->body());
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
            throw new CantResolveCoinexException("Can't Resolve " . config('exchanges.binance.base_url'));
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

            throw new CoinexHasProblemException($exceptionMessage);
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
