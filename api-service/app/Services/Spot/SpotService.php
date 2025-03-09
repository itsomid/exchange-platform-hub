<?php

namespace App\Services\Spot;

use App\Enums\LockedBalanceTypeEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Exceptions\V1\OTC\InsufficientBalanceException;
use App\Helpers\Math;
use App\Models\LockedBalanceDetail;
use App\Repositories\DTO\SpotOrder\SpotOrderCreateRequestDTO;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\SpotOrderRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Spot\DTO\SpotTradeRequestDTO;
use Throwable;

class SpotService
{
    public function __construct(
        private readonly MarketRepositoryInterface $marketRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly SpotOrderRepositoryInterface $spotOrderRepository,
    ) {}

    public function trade(SpotTradeRequestDTO $requestDTO): void
    {
        $market = $this->marketRepository->getMarketById($requestDTO->getMarketId());

        $type = $requestDTO->getType();
        $side = $requestDTO->getSide();
        $quantity = $requestDTO->getQuantity();
        $price = $requestDTO->getPrice();

        // Determine currency for balance check
        $currency = ($side === SpotOrderSideEnum::BUY)
            ? $market->quote_currency
            : $market->base_currency;

        // Determine commission type (maker/taker) and rate
        $feeRate = 0.001; // 0.1% commission

        // Calculate required balance and commission
        $tradeAmount = ($side === SpotOrderSideEnum::BUY)
            ? Math::mul($quantity, $price)
            : $quantity;

        $commission = Math::mul($tradeAmount, $feeRate); // 0.1% maker/taker fee
        $requiredAmount = Math::add($tradeAmount, $commission);

        // Check wallet balance (with pessimistic locking)
        $wallet = $this->walletRepository->getOneOrCreateByCurrencyWithLock($currency, $requestDTO->getUserId());

        if (Math::comp($wallet->balance, $requiredAmount) === -1) {
            throw new InsufficientBalanceException;
        }

        // Update wallet balances
        $wallet->balance = Math::sub($wallet->balance, $requiredAmount);
        $wallet->locked_balance = Math::add($wallet->locked_balance, $requiredAmount);

        try {
            $wallet->save();

            // Create spot order with commission details
            $spotOrder = $this->spotOrderRepository->create(
                resolve(SpotOrderCreateRequestDTO::class)
                    ->setSide($side)
                    ->setPrice($price)
                    ->setStatus(SpotOrderStatusEnum::PENDING)
                    ->setMarketId($market->id)
                    ->setQuantity($quantity)
                    ->setFilledQuantity(0)
                    ->setType($type)
                    ->setUserId($requestDTO->getUserId())
            );

            // Record locked balance details
            LockedBalanceDetail::query()->create([
                'wallet_id' => $wallet->id,
                'amount' => $requiredAmount,
                'type' => LockedBalanceTypeEnum::SPOT,
                'spot_order_id' => $spotOrder->id,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function lists() {}
}
