<?php

namespace App\Services\Spot;

use App\Enums\LockedBalanceTypeEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Enums\SpotOrderSourceEnum;
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

        // Validate quantity for both market and limit orders
        if (is_null($quantity) || Math::comp($quantity, '0') !== 1) {
            throw new InvalidArgumentException('مقدار باید بزرگتر از صفر و معتبر باشد.');
        }


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
                    ->setSource($requestDTO->getSource())
            );

            $response->setSpotOrderModel($spotOrder);

            // Record locked balance details
            if ($type !== SpotOrderTypeEnum::MARKET && $requestDTO->getSource() !== SpotOrderSourceEnum::BOT) {
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
            throw $exception; // Re-throw the exception instead of silently continuing
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
                ->setMarketId($requestDTO->getMarketId())
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

            // Check if this is a bot order
            if ($order->source === SpotOrderSourceEnum::BOT) {
                $this->cancelBotOrder($userId, $order);
            } else {
                $this->cancelRegularOrder($userId, $order);
            }

            DB::commit();
            OrderBookUpdated::dispatch($order->market->id);
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * Cancel regular user orders (non-bot orders)
     */
    private function cancelRegularOrder(int $userId, $order): void
    {
        $lockedDetail = $this->lockedBalanceRepository->getOne($order->id, LockedBalanceTypeEnum::SPOT);

        $amountRefund = $lockedDetail->amount;
        //Partial Matched
        if (Math::comp($order->filled_quantity, '0') !== 0) {
            $amountRefund = Math::sub($lockedDetail->amount, $order->getFilledValue());
        }

        $market = $order->market;
        $this->lockedBalanceRepository->deleteSpotOrderLockedBalance($order->id);

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
    }

    /**
     * Cancel bot orders - clears locked_balance since no locked_balance_details exist
     */
    public function cancelBotOrder(int $userId, $order): void
    {
        $market = $order->market;

        // Calculate the amount that should be refunded
        if ($order->side === SpotOrderSideEnum::BUY) {
            $currency = $market->quote_currency;
            $amountRefund = Math::mul($order->quantity, $order->price);
            // For partial fills, subtract the filled value
            if (Math::comp($order->filled_quantity, '0') !== 0) {
                $amountRefund = Math::sub($amountRefund, $order->getFilledValue());
            }
        } else {
            $currency = $market->base_currency;
            $amountRefund = $order->quantity;
            // For partial fills, subtract the filled quantity
            if (Math::comp($order->filled_quantity, '0') !== 0) {
                $amountRefund = Math::sub($amountRefund, $order->filled_quantity);
            }
        }

        $wallet = $this->walletRepository->getWalletWithLock($currency, $userId);

        // Set locked_balance to zero for bot orders
        $wallet->update(['locked_balance' => '0']);

        $order->update([
            'status' => SpotOrderStatusEnum::CANCELED,
        ]);
    }
}
