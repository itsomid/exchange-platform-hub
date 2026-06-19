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
use App\Models\SpotOrder;
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
                // Use the actual best ask from the order book, not the external exchange rate.
                // The external exchange rate can differ significantly from local order book prices
                // and would allow orders through that cannot be funded at the real execution price.

                $bestAskPrice = $this->getBestOppositePrice($requestDTO->getMarketId(), $side);
             
                if ($bestAskPrice === null) {
                    throw new InvalidArgumentException('...');
                }
                $tradeAmount = Math::mul($quantity, $bestAskPrice);
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

    /**
     * Get best opposite price (best ask for BUY, best bid for SELL) from both DB and hybrid order book
     */
    private function getBestOppositePrice(int $marketId, SpotOrderSideEnum $side): ?string
    {
        $oppositeSide = $side === SpotOrderSideEnum::BUY ? SpotOrderSideEnum::SELL : SpotOrderSideEnum::BUY;

        // DB query for best opposite price
        $dbQuery = SpotOrder::query()
            ->where('market_id', $marketId)
            ->where('status', SpotOrderStatusEnum::OPEN)
            ->where('side', $oppositeSide)
            ->whereNotNull('price');

        if ($side === SpotOrderSideEnum::BUY) {
            $dbQuery->orderBy('price', 'asc'); // best ask = lowest sell
        } else {
            $dbQuery->orderBy('price', 'desc'); // best bid = highest buy
        }
        $dbBest = optional($dbQuery->first())->price;

        // In-memory (hybrid) order book data
        $orderBook = $this->hybridOrderBook->getLatestOrderBook($marketId, 50);
        $bestMemory = null;
        if ($side === SpotOrderSideEnum::BUY) {
            // asks array (SELL side) objects or arrays
            $asks = $orderBook['asks'] ?? [];
            foreach ($asks as $ask) {
                $askPrice = is_array($ask) ? ($ask['price'] ?? null) : ($ask->price ?? null);
                if ($askPrice === null) continue;
                if ($bestMemory === null || Math::comp($askPrice, $bestMemory) === -1) {
                    $bestMemory = $askPrice;
                }
            }
        } else {
            // bids array (BUY side)
            $bids = $orderBook['bids'] ?? [];
            foreach ($bids as $bid) {
                $bidPrice = is_array($bid) ? ($bid['price'] ?? null) : ($bid->price ?? null);
                if ($bidPrice === null) continue;
                if ($bestMemory === null || Math::comp($bidPrice, $bestMemory) === 1) {
                    $bestMemory = $bidPrice;
                }
            }
        }

        // Decide best overall
        if ($dbBest === null) return $bestMemory;
        if ($bestMemory === null) return $dbBest;
        if ($side === SpotOrderSideEnum::BUY) {
            // choose lower ask
            return Math::comp($bestMemory, $dbBest) === -1 ? $bestMemory : $dbBest;
        }
        // choose higher bid
        return Math::comp($bestMemory, $dbBest) === 1 ? $bestMemory : $dbBest;
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
     * Cancel bot orders without any locked_balance operations.
     * Bot orders no longer lock funds; just update status accordingly.
     */
    public function cancelBotOrder(int $userId, $order): void
    {
        // Simply set status based on whether order was partially filled

        if (Math::comp($order->filled_quantity, '0') !== 0) {
            $order->update(['status' => SpotOrderStatusEnum::PARTIALLY_FILLED_CANCELED]);
        } else {
            $order->update(['status' => SpotOrderStatusEnum::CANCELED]);
        }
    }
}
