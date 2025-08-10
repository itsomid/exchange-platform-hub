<?php

namespace App\Services\Spot;

use App\Enums\LockedBalanceTypeEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Events\OrderBookUpdated;
use App\Exceptions\V1\Wallet\InsufficientBalanceException;
use App\Helpers\Math;
use App\Models\LockedBalanceDetail;
use App\Repositories\DTO\SpotOrder\SpotOrderCreateRequestDTO;
use App\Repositories\DTO\SpotOrder\TradeListRequestDTO;
use App\Repositories\Interfaces\LockedBalanceRepositoryInterface;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\SpotOrderRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Spot\DTO\SpotOrderListsRequestDTO;
use App\Services\Spot\DTO\SpotOrderListsResponseDTO;
use App\Services\Spot\DTO\SpotOrderRequestDTO;
use App\Services\Spot\DTO\SpotOrderResponseDTO;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Throwable;

class SpotService
{
    public function __construct(
        private readonly MarketRepositoryInterface $marketRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly SpotOrderRepositoryInterface $spotOrderRepository,
        private readonly LockedBalanceRepositoryInterface $lockedBalanceRepository,
    ) {}

    public function trade(SpotOrderRequestDTO $requestDTO): SpotOrderResponseDTO
    {
        $response = resolve(SpotOrderResponseDTO::class);
        $market = $this->marketRepository->getMarketById($requestDTO->getMarketId());

        $type = $requestDTO->getType();
        $side = $requestDTO->getSide();
        $quantity = $requestDTO->getQuantity();

        $price = $requestDTO->getPrice();


        if ($type !== SpotOrderTypeEnum::MARKET) {
            if (is_null($price) || Math::comp($price, '0') !== 1) {
                throw new InvalidArgumentException('قیمت باید بزرگتر از صفر و معتبر باشد.');
            }
        }

        // Determine currency for balance check
        $currency = ($side === SpotOrderSideEnum::BUY)
            ? $market->quote_currency
            : $market->base_currency;



        if ($side === SpotOrderSideEnum::BUY) {
            if ($type === SpotOrderTypeEnum::MARKET) {
                $tradeAmount = Math::mul($quantity, $market->exchangePrice->price);
            } else {
                $tradeAmount = Math::mul($quantity, $price);
            }
        } else {
            $tradeAmount = $quantity;
        }
        // Check wallet balance (with pessimistic locking)
        $wallet = $this->walletRepository->getOneOrCreateByCurrencyWithLock($currency, $requestDTO->getUserId());

        if (Math::comp($wallet->available_balance, $tradeAmount) === -1) {
            throw new InsufficientBalanceException("Insufficient {$currency} balance.");
        }

        // Update wallet balances only if it's not a market order
        if ($type !== SpotOrderTypeEnum::MARKET) {
            $wallet->locked_balance = Math::add($wallet->locked_balance, $tradeAmount);
        }

        try {
            $wallet->save();

            // Create spot order with commission details
            $spotOrder = $this->spotOrderRepository->create(
                resolve(SpotOrderCreateRequestDTO::class)
                    ->setSide($side)
                    ->setPrice($price)
                    ->setStatus(SpotOrderStatusEnum::OPEN)
                    ->setMarketId($market->id)
                    ->setQuantity($quantity)
                    ->setFilledQuantity(0)
                    ->setType($type)
                    ->setUserId($requestDTO->getUserId())
            );

            $response->setSpotOrderModel($spotOrder);

            // Record locked balance details
            if ($type !== SpotOrderTypeEnum::MARKET) {
                LockedBalanceDetail::query()->create([
                    'wallet_id' => $wallet->id,
                    'amount' => $tradeAmount,
                    'type' => LockedBalanceTypeEnum::SPOT,
                    'spot_order_id' => $spotOrder->id,
                ]);
            }
            //Update Socket
            OrderBookUpdated::dispatch($requestDTO->getMarketId());
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    public function lists(SpotOrderListsRequestDTO $requestDTO): array
    {
        return $this->spotOrderRepository->lists(
            resolve(TradeListRequestDTO::class)
                ->setType($requestDTO->getType())
                ->setSide($requestDTO->getSide())
                ->setStatus($requestDTO->getStatus())
                ->setUserId($requestDTO->getUserId())
        )->map(function ($order) {

            //            $filledValue = $order->makerTrades->reduce(fn (int $carry, $item) => Math::add($carry, (Math::mul($item->price, $item->quantity))), 0);
            //            $filledValue = Math::add($filledValue, $order->takerTrades->reduce(fn (int $carry, $item) => Math::add($carry, (Math::mul($item->price, $item->quantity))), 0));
            $filledValue = formatNumberTrimZeros(bcmul($order->price, $order->filled_quantity, 8));
            return resolve(SpotOrderListsResponseDTO::class)
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

    public function getDetail(int $userId, int $orderId): SpotOrderListsResponseDTO
    {
        $order = $this->spotOrderRepository->getDetail(
            userId: $userId,
            orderId: $orderId
        );

        //        $filledValue = $order->makerTrades->reduce(fn (int $carry, $item) => Math::add($carry, (Math::mul($item->price, $item->quantity))), 0);
        //        $filledValue = Math::add($filledValue, $order->takerTrades->reduce(fn (int $carry, $item) => Math::add($carry, (Math::mul($item->price, $item->quantity))), 0));
        $filledValue = formatNumberTrimZeros(bcmul($order->price, $order->filled_quantity, 8));
        return resolve(SpotOrderListsResponseDTO::class)
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
    }

    public function cancel(int $userId, int $orderId): void
    {
        try {
            DB::beginTransaction();
            $order = $this->spotOrderRepository->getOneWithLock($orderId);
            if ($order->status !== SpotOrderStatusEnum::OPEN) {
                return;
            }
            $lockedDetail = $this->lockedBalanceRepository->getOne($orderId, LockedBalanceTypeEnum::SPOT);

            $amountRefund = $lockedDetail->amount;
            //Partial Matched
            if (Math::comp($order->filled_quantity, '0') !== 0) {
                $amountRefund = Math::sub($lockedDetail->amount, $order->getFilledValue());
            }

            $market = $order->market;
            $this->lockedBalanceRepository->deleteSpotOrderLockedBalance($orderId);

            if ($order->side === SpotOrderSideEnum::BUY) {
                $currency = $market->quote_currency;
            } else {
                $currency = $market->base_currency;
            }
            $wallet = $this->walletRepository->getWalletWithLock($currency, $userId);

            $wallet->decrement('locked_balance', $amountRefund);

            $order->update([
                'status' => SpotOrderStatusEnum::CANCELED,
            ]);
            DB::commit();
            OrderBookUpdated::dispatch($market->id);
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();
        }
    }
}
