<?php

namespace App\Services\Spot;

use App\Enums\LockedBalanceTypeEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Events\OrderBookUpdated;
use App\Exceptions\V1\OTC\InsufficientBalanceException;
use App\Helpers\Math;
use App\Models\LockedBalanceDetail;
use App\Repositories\DTO\SpotOrder\SpotOrderCreateRequestDTO;
use App\Repositories\DTO\SpotOrder\TradeListRequestDTO;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\SpotOrderRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Spot\DTO\SpotTradeListsRequestDTO;
use App\Services\Spot\DTO\SpotTradeListsResponseDTO;
use App\Services\Spot\DTO\SpotTradeRequestDTO;
use App\Services\Spot\DTO\SpotTradeResponseDTO;
use Throwable;

class SpotService
{
    public function __construct(
        private readonly MarketRepositoryInterface $marketRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly SpotOrderRepositoryInterface $spotOrderRepository,
    ) {}

    public function calcCommission($tradeAmount): string
    {
        $feeRate = 0.001; // 0.1% commission

        return $commission = Math::mul($tradeAmount, $feeRate);
    }

    public function trade(SpotTradeRequestDTO $requestDTO): SpotTradeResponseDTO
    {
        $response = resolve(SpotTradeResponseDTO::class);
        $market = $this->marketRepository->getMarketById($requestDTO->getMarketId());

        $type = $requestDTO->getType();
        $side = $requestDTO->getSide();
        $quantity = $requestDTO->getQuantity();

        if ($type === SpotOrderTypeEnum::MARKET) {
            $priceType = $side === SpotOrderSideEnum::BUY ? 'buy_price' : 'sell_price';
            $price = $market->exchangePrice->$priceType;
        } else {
            $price = $requestDTO->getPrice();
        }
        // Determine currency for balance check
        $currency = ($side === SpotOrderSideEnum::BUY)
            ? $market->quote_currency
            : $market->base_currency;

        // Determine commission type (maker/taker) and rate

        $tradeAmount = ($side === SpotOrderSideEnum::BUY)
            ? Math::mul($quantity, $price)
            : $quantity;

        // Check wallet balance (with pessimistic locking)
        $wallet = $this->walletRepository->getOneOrCreateByCurrencyWithLock($currency, $requestDTO->getUserId());

        if (Math::comp($wallet->balance, $tradeAmount) === -1) {
            throw new InsufficientBalanceException;
        }

        // Update wallet balances
        $wallet->balance = Math::sub($wallet->balance, $tradeAmount);
        $wallet->locked_balance = Math::add($wallet->locked_balance, $tradeAmount);

        try {
            $wallet->save();

            // Create spot order with commission details
            $spotOrder = $this->spotOrderRepository->create(
                resolve(SpotOrderCreateRequestDTO::class)
                    ->setSide($side)
                    ->setPrice($type === SpotOrderTypeEnum::MARKET ? null : $price)
                    ->setStatus(SpotOrderStatusEnum::OPEN)
                    ->setMarketId($market->id)
                    ->setQuantity($quantity)
                    ->setFilledQuantity(0)
                    ->setType($type)
                    ->setUserId($requestDTO->getUserId())
            );
            $response->setSpotOrderModel($spotOrder);

            // Record locked balance details
            LockedBalanceDetail::query()->create([
                'wallet_id' => $wallet->id,
                'amount' => $tradeAmount,
                'type' => LockedBalanceTypeEnum::SPOT,
                'spot_order_id' => $spotOrder->id,
            ]);
            //Update Socket
            OrderBookUpdated::dispatch($requestDTO->getMarketId());
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    public function lists(SpotTradeListsRequestDTO $requestDTO): array
    {
        return $this->spotOrderRepository->lists(
            resolve(TradeListRequestDTO::class)
                ->setType($requestDTO->getType())
                ->setSide($requestDTO->getSide())
                ->setStatus($requestDTO->getStatus())
                ->setUserId($requestDTO->getUserId())
        )->map(function ($order) {

            $filledValue = $order->makerTrades->reduce(fn (int $carry, $item) => Math::add($carry, (Math::mul($item->price, $item->quantity))), 0);
            $filledValue = Math::add($filledValue, $order->takerTrades->reduce(fn (int $carry, $item) => Math::add($carry, (Math::mul($item->price, $item->quantity))), 0));

            return resolve(SpotTradeListsResponseDTO::class)
                ->setId($order->id)
                ->setStatus($order->status)
                ->setSide($order->side)
                ->setType($order->type)
                ->setCommission(
                    Math::add(
                        $order->maker_commissions_sum_maker_commission_amount ?? 0,
                        $order->taker_commissions_sum_taker_commission_amount ?? 0
                    )
                )
                ->setFilledQuantity($order->filled_quantity)
                ->setQuantity($order->quantity)
                ->setPrice($order->price)
                ->setMarketName($order->market->base_currency, $order->market->quote_currency)
                ->setFilledValue($filledValue)
                ->setCreatedAt($order->created_at);
        })->toArray();
    }

    public function getLatestOrderBook(int $marketId, int $limit): array
    {
        return $this->spotOrderRepository->getLatestOrders($marketId, $limit);
    }
}
