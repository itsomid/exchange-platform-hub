<?php

namespace App\Services\Spot;

use App\Enums\LockedBalanceTypeEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Enums\SpotOrderSourceEnum;
use App\Jobs\BroadcastOrderBook;
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
use App\Services\SpotBot\HybridOrderBookService;
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
        private readonly HybridOrderBookService $hybridOrderBook,
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

        // For market orders, check if opposite side has orders (from both DB and Redis)
        if ($type === SpotOrderTypeEnum::MARKET) {
            if (!$this->hybridOrderBook->hasOrdersOnOppositeSide($requestDTO->getMarketId(), $side, $requestDTO->getUserId())) {
                throw new InvalidArgumentException('امکان ثبت سفارش بازار وجود ندارد.');
            }
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
            throw new InsufficientBalanceException(trans('exceptions.' . \App\Exceptions\V1\Wallet\InsufficientBalanceException::class, ['currency' => $currency]));
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
            BroadcastOrderBook::dispatch($requestDTO->getMarketId());
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
                ->setCommissionCurrency($order->getCommissionCurrency())
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
        // Use hybrid order book service to get orders from both database and Redis
        return $this->hybridOrderBook->getLatestOrderBook($marketId, $limit);
    }

    public function getDetail(int $userId, int $orderId): SpotOrderListsResponseDTO
    {
        $order = $this->spotOrderRepository->getDetail(
            userId: $userId,
            orderId: $orderId
        );

    
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
            ->setCommissionCurrency($order->getCommissionCurrency())
            ->setFilledQuantity($order->filled_quantity)
            ->setQuantity($order->quantity)
            ->setPrice($order->price)
            ->setMarketName($order->market->base_currency, $order->market->quote_currency)
            ->setFilledValue($filledValue)
            ->setCreatedAt($order->created_at);
    }

    public function cancel(int $userId, int $orderId)
    {
        try {
            DB::beginTransaction();
            $order = $this->spotOrderRepository->getOneWithLock($orderId);

            if ($order === null) {
                throw new InvalidArgumentException('سفارش موردنظر یافت نشد.');
            }

            if ($order->status !== SpotOrderStatusEnum::OPEN) {
                throw new InvalidArgumentException('این سفارش در وضعیت باز نیست و قابل لغو نیست.');
            }

            // Check if this is a bot order
            if ($order->source === SpotOrderSourceEnum::BOT) {
                $this->cancelBotOrder($userId, $order);
            } else {
                $this->cancelRegularOrder($userId, $order);
            }

            DB::commit();
            BroadcastOrderBook::dispatch($order->market->id);
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

        // Safety check: if LockedBalanceDetail doesn't exist (data inconsistency), log error and return
        if (!$lockedDetail) {
            \Illuminate\Support\Facades\Log::channel('spot-order-matching')->error("LockedBalanceDetail not found for order {$order->id} during cancellation. Possible data inconsistency.");
            throw new InvalidArgumentException('خطا در لغو سفارش: اطلاعات قفل موجودی یافت نشد.');
        }

        // The lockedDetail->amount is now accurate because it's updated during refunds
        // So we can safely use it as the amount to unlock
        $amountRefund = $lockedDetail->amount;

        // Note: We no longer need to recalculate based on filled_quantity and getFilledValue()
        // because LockedBalanceDetail->amount is kept up-to-date during partial matches
        // when refunds occur (see OrderMatchingEngine::updateWallets)

        $market = $order->market;

        // Update description before deleting locked balance
        $description = '';
        if (Math::comp($order->filled_quantity, '0') !== 0) {
            // Partially filled order
            $description = "لغو سفارش اسپات #{$order->id} - پر شده: " . formatNumberTrimZeros($order->filled_quantity) . " از " . formatNumberTrimZeros($order->quantity);
        } else {
            // Fully unfilled order
            $description = "لغو سفارش اسپات #{$order->id} - بدون پر شدن";
        }

        $lockedDetail->update(['description' => $description]);

        $this->lockedBalanceRepository->deleteSpotOrderLockedBalance($order->id);

        if ($order->side === SpotOrderSideEnum::BUY) {
            $currency = $market->quote_currency;
        } else {
            $currency = $market->base_currency;
        }
        // Use repository guarded method to avoid negative locked_balance and ensure row lock
        $this->walletRepository->decreaseLockedBalance($userId, $currency, $amountRefund);

        // Set status based on whether order was partially filled
        if (Math::comp($order->filled_quantity, '0') !== 0) {
            // Order was partially filled
            $order->update([
                'status' => SpotOrderStatusEnum::PARTIALLY_FILLED_CANCELED,
            ]);
        } else {
            // Order was not filled at all
            $order->update([
                'status' => SpotOrderStatusEnum::CANCELED,
            ]);
        }
    }

    /**
     * Cancel bot orders - now uses LockedBalanceDetail for accurate tracking
     * LockedBalanceDetail is created when bot order is persisted from Redis to DB
     */
    public function cancelBotOrder(int $userId, $order): void
    {
        $lockedDetail = $this->lockedBalanceRepository->getOne($order->id, LockedBalanceTypeEnum::SPOT);

        // Check if LockedBalanceDetail exists (should exist for orders persisted from Redis)
        // For orders that were never matched (still in Redis), they won't have LockedBalanceDetail
        // but they're cancelled via cancelBotOrdersForMarket which handles Redis orders separately
        if (!$lockedDetail) {
            // Fallback for edge cases: calculate refund manually if LockedBalanceDetail doesn't exist
            // This can happen if order was created before the fix or if there's a race condition
            \Illuminate\Support\Facades\Log::channel('spot-order-matching')->warning(
                "LockedBalanceDetail not found for bot order {$order->id} during cancellation. Using fallback calculation."
            );

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

            // Use repository guarded method to avoid negative locked_balance and ensure row lock
            $this->walletRepository->decreaseLockedBalance($userId, $currency, $amountRefund);
        } else {
            // Use LockedBalanceDetail amount (which is kept up-to-date during partial fills)
            $amountRefund = $lockedDetail->amount;

            $market = $order->market;
            if ($order->side === SpotOrderSideEnum::BUY) {
                $currency = $market->quote_currency;
            } else {
                $currency = $market->base_currency;
            }

            // Update description before deleting locked balance
            $description = '';
            if (Math::comp($order->filled_quantity, '0') !== 0) {
                // Partially filled order
                $description = "لغو سفارش ربات #{$order->id} - پر شده: " . formatNumberTrimZeros($order->filled_quantity) . " از " . formatNumberTrimZeros($order->quantity);
            } else {
                // Fully unfilled order
                $description = "لغو سفارش ربات #{$order->id} - بدون پر شدن";
            }

            $lockedDetail->update(['description' => $description]);
            $this->lockedBalanceRepository->deleteSpotOrderLockedBalance($order->id);

            // Use repository guarded method to avoid negative locked_balance and ensure row lock
            $this->walletRepository->decreaseLockedBalance($userId, $currency, $amountRefund);
        }

        // Set status based on whether order was partially filled
        if (Math::comp($order->filled_quantity, '0') !== 0) {
            $order->update(['status' => SpotOrderStatusEnum::PARTIALLY_FILLED_CANCELED]);
        } else {
            $order->update(['status' => SpotOrderStatusEnum::CANCELED]);
        }
    }
}
