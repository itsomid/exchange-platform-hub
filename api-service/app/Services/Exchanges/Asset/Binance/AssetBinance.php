<?php

namespace App\Services\Exchanges\Asset\Binance;

use App\Enums\SpotStatusEnum;
use App\Repositories\CurrencyRepository;
use App\Services\Exchanges\AdminNotification;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AssetBinance implements AssetInterface
{
    public function getBalance(): array
    {
        $response = BinanceRequest::sendRequest('GET', '/api/v3/account');
        $json = $response->json();

        if (!$response->ok() || !isset($json['balances']) || !is_array($json['balances'])) {
            return [];
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
            $filters = BinanceRequest::symbolFilters($symbol);
            $currency = (new CurrencyRepository())->getOne($request->getCurrency());
            $quantity = BinanceOrderFormatter::formatQuantity(
                $request->getQuantity(),
                BinanceOrderFormatter::stepFor($orderType, $filters),
                $currency?->amount_precision ?? 8
            );

            $minQty = BinanceOrderFormatter::minQtyFor($orderType, $filters) ?? '0';
            if (bccomp($quantity, '0', 18) !== 1 || bccomp($quantity, $minQty, 18) === -1) {
                AdminNotification::sendSpotTradingIsTooSmall($symbol, $request->getQuantity());

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

            $minNotional = BinanceOrderFormatter::minNotionalFor($orderType, $filters);
            if ($minNotional !== null) {
                $notionalPrice = $params['price'] ?? BinanceOrderFormatter::plainDecimal(BinanceRequest::averagePrice($symbol));
                if (bccomp(bcmul($quantity, $notionalPrice, 18), $minNotional, 18) === -1) {
                    AdminNotification::sendSpotTradingIsTooSmall($symbol, $request->getQuantity());

                    return $this->failedOrder(SpotStatusEnum::AmountTooSmall, -1013, 'ارزش سفارش کمتر از حداقل مجاز بایننس است.');
                }
            }

            $response = BinanceRequest::sendRequest('POST', '/api/v3/order', $params);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->failedOrder(SpotStatusEnum::ConnectionLosses, 0, 'عدم ارتباط با صرافی مرجع، لطفا بعدا تلاش کنید.');
        }

        $json = $response->json();
        if (! is_array($json)) {
            $json = [];
        }

        if (! $response->ok() || (isset($json['code']) && (int) $json['code'] !== 0)) {
            $errorCode = (int) ($json['code'] ?? 0);
            $errorMsg = (string) ($json['msg'] ?? ($json['message'] ?? $response->body()));
            $status = BinanceOrderFormatter::mapError($errorCode, $errorMsg);

            Log::channel('ref-exchange')->error('Binance placeOrder failed', [
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'response' => $json ?: $response->body(),
            ]);

            // Not-enough-balance is reported by the caller (OTCService) with full context.
            match ($status) {
                SpotStatusEnum::AmountTooSmall => AdminNotification::sendSpotTradingIsTooSmall($symbol, $request->getQuantity()),
                SpotStatusEnum::PriceDifferenceTooLarge => AdminNotification::sendPriceDifferenceTooLarge($symbol, $request->getQuantity(), $errorMsg),
                SpotStatusEnum::NotEnoughBalance => null,
                default => AdminNotification::logError(
                    $symbol,
                    $request->getQuantity(),
                    $response->body(),
                    $request->getTradeType(),
                    $request->getUserId(),
                    $request->getOrderId(),
                ),
            };

            return $this->failedOrder($status, $errorCode, $errorMsg);
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

    private function failedOrder(SpotStatusEnum $status, int $errorCode, string $errorMessage): BuyDTOResponse
    {
        return resolve(BuyDTOResponse::class)
            ->setSpotStatus($status)
            ->setErrorCode($errorCode)
            ->setErrorMessage($errorMessage)
            ->setIsDone(false);
    }
}
