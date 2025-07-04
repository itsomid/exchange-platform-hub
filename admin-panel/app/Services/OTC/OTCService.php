<?php

namespace App\Services\OTC;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Market;
use App\Models\OTCOrder;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\OTC\DTO\OTCRequestDTO;
use App\Services\Referral\ReferralCommissionService;
use Illuminate\Support\Facades\DB;
use Throwable;

class OTCService
{
    /**
     * The user ID for your exchange (e.g., to handle fees or internal transfers).
     *
     * @var int
     */
    protected int $bitexroomUserId;
    protected $referralCommissionService;

    public function __construct(ReferralCommissionService $referralCommissionService)
    {
        $this->bitexroomUserId = config('bitexroom.user_id');
        $this->referralCommissionService = $referralCommissionService;
    }

    /**
     * Places a buy (OTC) order.
     *
     * @param OTCRequestDTO $requestDTO
     * @return bool
     * @throws Throwable
     */
    public function buy(OTCRequestDTO $requestDTO): bool
    {
        return $this->processTransaction($requestDTO, 'buy');
    }

    /**
     * Places a sell (OTC) order.
     *
     * @param OTCRequestDTO $requestDTO
     * @return bool
     * @throws Throwable
     */
    public function sell(OTCRequestDTO $requestDTO): bool
    {
        return $this->processTransaction($requestDTO, 'sell');
    }

    /**
     * Handles the primary logic for both buy and sell OTC transactions.
     *
     * @param OTCRequestDTO $requestDTO
     * @param string $type
     * @return bool
     * @throws Throwable
     */
    private function processTransaction(OTCRequestDTO $requestDTO, string $type): bool
    {
        try {
            return DB::transaction(function () use ($requestDTO, $type) {
                // 1. Find the market
                $market = Market::query()->findOrFail($requestDTO->getMarketId());

                // 2. Get the relevant wallets (locked for update to avoid race conditions)
                $sellerWallet = $this->getWallet($requestDTO->getSellerUserId(), $market->base_currency);
                $buyerWallet = $this->getWallet($requestDTO->getBuyerUserId(), $market->base_currency);
                $sellerQuoteWallet = $this->getWallet($requestDTO->getSellerUserId(), $market->quote_currency);
                $buyerQuoteWallet = $this->getWallet($requestDTO->getBuyerUserId(), $market->quote_currency);

                // 3. Calculate fees, amounts, and final received amounts
                [$fee, $amountInQuoteCurrency, $receivedAmount, $orderPrice] =
                    $this->calculateFeesAndAmounts($requestDTO, $type, $market);

                // 4. Validate balances
                $this->validateBalances($type, $buyerQuoteWallet, $sellerWallet, $requestDTO->getQuantity(), $amountInQuoteCurrency, $receivedAmount, $market);

                // 5. Create OTC order
                $otcOrder = $this->createOTCOrder(
                    $type,
                    ($type === 'buy') ? $requestDTO->getBuyerUserId() : $requestDTO->getSellerUserId(),
                    $market->id,
                    $requestDTO->getQuantity(),
                    $orderPrice,
                    $fee
                );

                if ($otcOrder->user->introducer_code) {
                    $this->referralCommissionService->processReferralCommission($otcOrder, $fee);
                }

                // 6. Process wallet balances & transactions
                if ($type === 'buy') {
                    $this->processBuyTransactions(
                        $requestDTO->getBuyerUserId(),
                        $buyerWallet,
                        $sellerWallet,
                        $buyerQuoteWallet,
                        $sellerQuoteWallet,
                        $requestDTO->getQuantity(),
                        $amountInQuoteCurrency,
                        $receivedAmount,
                        $fee,
                        $otcOrder
                    );
                } else {
                    $this->processSellTransactions(
                        $requestDTO->getSellerUserId(),
                        $sellerWallet,
                        $buyerWallet,
                        $sellerQuoteWallet,
                        $buyerQuoteWallet,
                        $requestDTO->getQuantity(),
                        $amountInQuoteCurrency,
                        $receivedAmount,
                        $fee,
                        $otcOrder
                    );
                }


                return true;
            });
        } catch (Throwable $exception) {
            report($exception);
            throw $exception;
        }
    }

    /**
     * Retrieve the user's wallet for the given currency, locked for update.
     *
     * @param int $userId
     * @param string $currencySymbol
     * @return Wallet
     */
    private function getWallet(int $userId, string $currencySymbol): Wallet
    {
        return Wallet::query()
            ->where('user_id', $userId)
            ->where('currency_symbol', $currencySymbol)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Validate that the buyer or seller have sufficient balances for the transaction.
     *
     * @param string $type
     * @param Wallet $buyerQuoteWallet
     * @param Wallet $sellerWallet
     * @param float $quantity
     * @param float $amountInQuoteCurrency
     * @param float $receivedAmount
     * @param Market $market
     * @throws InsufficientBalanceException
     */
    private function validateBalances(
        string $type,
        Wallet $buyerQuoteWallet,
        Wallet $sellerWallet,
        float  $quantity,
        float  $amountInQuoteCurrency,
        float  $receivedAmount,
        Market $market
    )
    {
        if ($type === 'buy') {
            // Buyer must have enough quote currency
            if ($buyerQuoteWallet->access_balance < $amountInQuoteCurrency) {
                throw new InsufficientBalanceException(
                    "Buyer does not have enough {$market->quote_currency}."
                );
            }
            // Seller must have at least the 'receivedAmount' in base currency
            if ($sellerWallet->access_balance < $receivedAmount) {
                throw new InsufficientBalanceException(
                    "Seller does not have enough {$market->base_currency}."
                );
            }
        } else { // 'sell'
            // Seller must have enough base currency
            if ($sellerWallet->access_balance < $quantity) {
                throw new InsufficientBalanceException(
                    "Seller does not have enough {$market->base_currency}."
                );
            }
            // Buyer must have enough quote currency
            if ($buyerQuoteWallet->access_balance < $amountInQuoteCurrency) {
                throw new InsufficientBalanceException(
                    "Buyer does not have enough {$market->quote_currency}."
                );
            }
        }
    }

    /**
     * Calculate fees, amounts, and final values for the transaction.
     *
     * @param OTCRequestDTO $requestDTO
     * @param string $type
     * @param Market $market
     * @return array          [$fee, $amountInQuoteCurrency, $receivedAmount, $orderPrice]
     */
    private function calculateFeesAndAmounts(OTCRequestDTO $requestDTO, string $type, Market $market): array
    {
        $otcBuyFee = Setting::getSetting('otc_buy_fee') / 100;
        $otcSellFee = Setting::getSetting('otc_sell_fee') / 100;
        $quantity = $requestDTO->getQuantity();

        if ($type === 'buy') {
            $orderPrice = $market->activeExchangePrice->exchange_sell_price;
            $amountInQuoteCurrency = bcmul($orderPrice, $quantity, 8);
            $fee = bcmul($quantity, $otcBuyFee, 8);
            $receivedAmount = bcsub($quantity, $fee, 8);
        } else {
            $orderPrice = $market->activeExchangePrice->exchange_buy_price;
            $amountInQuoteCurrency = bcmul($orderPrice, $quantity, 8);
            $fee = bcmul($amountInQuoteCurrency, $otcSellFee, 8);
            $receivedAmount = bcsub($amountInQuoteCurrency, $fee, 8);
        }

        return [
            (float)$fee,
            (float)$amountInQuoteCurrency,
            (float)$receivedAmount,
            (float)$orderPrice
        ];
    }

    /**
     * Create the OTC order record in the database.
     *
     * @param string $type
     * @param int $userId
     * @param int $marketId
     * @param float $quantity
     * @param float $price
     * @param float $fee
     * @return OTCOrder
     */
    private function createOTCOrder(
        string $type,
        int    $userId,
        int    $marketId,
        float  $quantity,
        float  $price,
        float  $fee
    ): OTCOrder
    {
        return OTCOrder::query()->create([
            'user_id' => $userId,
            'market_id' => $marketId,
            'quantity' => $quantity,
            'price' => $price,
            'fee' => $fee,
            'type' => $type,
            'status' => OTCOrderStatusEnum::SUCCESS,
        ]);
    }

    /**
     * Process the wallet updates and transactions for a BUY order.
     */
    private function processBuyTransactions(
        int      $buyerUserId,
        Wallet   $buyerWallet,
        Wallet   $sellerWallet,
        Wallet   $buyerQuoteWallet,
        Wallet   $sellerQuoteWallet,
        float    $quantity,
        float    $amountInQuoteCurrency,
        float    $receivedAmount,
        float    $fee,
        OTCOrder $otcOrder
    ): void
    {
        // Buyer receives base currency (minus fee)
        $buyerWallet->increment('balance', $receivedAmount);
        $this->createTransaction(
            $buyerUserId,
            $buyerWallet,
            $otcOrder,
            $receivedAmount,
            TransactionTypeEnum::BUY->value,
            $quantity,
            'خرید'
        );

        // Buyer pays quote currency
        $buyerQuoteWallet->decrement('balance', $amountInQuoteCurrency);
        $this->createTransaction(
            $buyerUserId,
            $buyerQuoteWallet,
            $otcOrder,
            -$amountInQuoteCurrency,
            TransactionTypeEnum::SELL->value,
            $quantity,
            'خرید'
        );

        // Seller receives quote currency
        $sellerQuoteWallet->increment('balance', $amountInQuoteCurrency);
        $this->createTransaction(
            $this->bitexroomUserId,
            $sellerQuoteWallet,
            $otcOrder,
            $amountInQuoteCurrency,
            TransactionTypeEnum::BUY->value,
            $quantity,
            'فروش'
        );

        // Seller loses base currency
        $sellerWallet->decrement('balance', $receivedAmount);
        $this->createTransaction(
            $this->bitexroomUserId,
            $sellerWallet,
            $otcOrder,
            -$receivedAmount,
            TransactionTypeEnum::SELL->value,
            $quantity,
            'فروش'
        );

        // Exchange collects fee in base currency
        if ($fee > 0) {
            $this->createCommissionTransaction($sellerWallet, $otcOrder, $fee, $quantity);
        }
    }

    /**
     * Process the wallet updates and transactions for a SELL order.
     */
    private function processSellTransactions(
        int      $sellerUserId,
        Wallet   $sellerWallet,
        Wallet   $buyerWallet,
        Wallet   $sellerQuoteWallet,
        Wallet   $buyerQuoteWallet,
        float    $quantity,
        float    $amountInQuoteCurrency,
        float    $receivedAmount,
        float    $fee,
        OTCOrder $otcOrder
    ): void
    {
        // Seller pays base currency
        $sellerWallet->decrement('balance', $quantity);
        $this->createTransaction(
            $sellerUserId,
            $sellerWallet,
            $otcOrder,
            -$quantity,
            TransactionTypeEnum::SELL->value,
            $quantity,
            'فروش'
        );

        // Seller receives quote currency (minus fee)
        $sellerQuoteWallet->increment('balance', $receivedAmount);
        $this->createTransaction(
            $sellerUserId,
            $sellerQuoteWallet,
            $otcOrder,
            $receivedAmount,
            TransactionTypeEnum::BUY->value,
            $quantity,
            'فروش'
        );

        // Buyer pays quote currency
        $buyerQuoteWallet->decrement('balance', $receivedAmount);
        $this->createTransaction(
            $this->bitexroomUserId,
            $buyerQuoteWallet,
            $otcOrder,
            -$receivedAmount,
            TransactionTypeEnum::SELL->value,
            $quantity,
            'خرید'
        );

        // Buyer receives base currency
        $buyerWallet->increment('balance', $quantity);
        $this->createTransaction(
            $this->bitexroomUserId,
            $buyerWallet,
            $otcOrder,
            $quantity,
            TransactionTypeEnum::BUY->value,
            $quantity,
            'خرید'
        );

        // Exchange collects fee in quote currency
        if ($fee > 0) {
            $this->createCommissionTransaction($buyerQuoteWallet, $otcOrder, $fee, $quantity);
        }
    }

    /**
     * Create a transaction record in the database.
     */
    private function createTransaction(
        int      $userId,
        Wallet   $wallet,
        OTCOrder $otcOrder,
        float    $amount,
        string   $type,
        float    $quantity,
        string   $description
    ): void
    {
        Transaction::query()->create([
            'user_id' => $userId,
            'wallet_id' => $wallet->id,
            'otc_order_id' => $otcOrder->id,
            'balance' => $wallet->balance,
            'amount' => $amount,
            'type' => $type,
            'subtype' => TransactionSubTypeEnum::OTC,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => sprintf(
                '%s %s %s به قیمت %s تتر',
                $description,
                formatNumberTrimZeros($quantity),
                $otcOrder->market->base_currency,
                formatNumberTrimZeros($otcOrder->price)
            ),
        ]);
    }

    /**
     * Create a commission transaction for the exchange user.
     */
    private function createCommissionTransaction(
        Wallet   $wallet,
        OTCOrder $otcOrder,
        float    $fee,
        float    $quantity
    ): void
    {
        Transaction::query()->create([
            'user_id' => $this->bitexroomUserId,
            'wallet_id' => $wallet->id,
            'otc_order_id' => $otcOrder->id,
            'balance' => $wallet->balance,
            'amount' => $fee,
            'type' => TransactionTypeEnum::FEE,
            'subtype' => TransactionSubTypeEnum::OTC,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => "کارمزد معامله {$otcOrder->market->base_currency} به ارزش "
                . formatNumberTrimZeros($quantity),
        ]);
    }
    ///report////
    public function totalOTCOrder(int $userId, string $currencySymbol, OTCOrderTypeEnum $type)
    {

        $market = Market::where('base_currency',$currencySymbol)->first();

        if (!$market) {
            return 0; // or handle it differently
        }

        return OTCOrder::where('user_id', $userId)
            ->where('market_id', $market->id)
            ->where('type', $type)
            ->sum('quantity');
    }

    public function totalOTCOrderValue(int $userId, string $currencySymbol, OTCOrderTypeEnum $type)
    {
        $market = Market::where('base_currency',$currencySymbol)->first();

        if (!$market) {
            return 0; // or handle it differently
        }
        return OTCOrder::where('user_id', $userId)
            ->where('market_id', $market->id)
            ->where('type', $type)
            ->sum(DB::raw('quantity * price'));
    }


}
