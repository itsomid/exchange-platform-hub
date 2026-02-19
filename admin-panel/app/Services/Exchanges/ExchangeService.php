<?php

namespace App\Services\Exchanges;

use App\Enums\RefExchangeSellStatusEnum;
use App\Enums\SpotStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\Exchange\CoinexWithdrawalException;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\OTCOrder;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Repositories\ExchangeRepository;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use App\Services\Exchanges\DTO\ChargeCurrencyRequestDTO;
use App\Services\Exchanges\DTO\ChargeCurrencyResponseDTO;
use App\Services\Exchanges\DTO\TriggerRefExchangeSellResponseDTO;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExchangeService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly MarketRepositoryInterface $marketRepository,
        private readonly ExchangeRepository $exchangeRepository,
        private readonly WalletRepositoryInterface $walletRepository,
    ) {}

    public function chargeCurrency(ChargeCurrencyRequestDTO $requestDTO): ChargeCurrencyResponseDTO
    {
        try {

            if ($requestDTO->getExchangeSlug()) {
                $exchange = $this->exchangeRepository->getExchangeBySlug($requestDTO->getExchangeSlug());
            } else {
                $exchange = $this->exchangeRepository->getActiveExchange();
            }

            $asset = AssetFactory::make($exchange->slug);

            $bitexroomWallet = $this->walletService->getExchangeWallet($requestDTO->getCurrency());

            $chain = WalletChain::query()
                ->firstOrCreate(
                    [
                        'wallet_id' => $bitexroomWallet->id,
                        'currency_chain' => $requestDTO->getCurrencyChain(),
                    ]
                );

            $response = $asset->withdraw(
                resolve(WithdrawRequestDTO::class)
                    ->setAddress($chain->address)
                    ->setChain($requestDTO->getCurrencyChain())
                    ->setAmount($requestDTO->getQuantity())
                    ->setWithdrawMethod(WithdrawMethodEnum::ON_CHAIN)
                    ->setCurrency($bitexroomWallet->currency_symbol)
            );

            // Continue with successful withdrawal processing
            ExchangeAssetsWithdrawal::query()
                ->create([
                    'withdrawal_id' => $response->getWithdrawId(),
                    'exchange' => $exchange->slug,
                    'currency_symbol' => $bitexroomWallet->currency_symbol,
                    'currency_chain' => $requestDTO->getCurrencyChain(),
                    'fee_currency' => $response->getCurrencyFee(),
                    'fee' => $response->getFee(),
                    'amount' => $response->getAmount(),
                    'actual_amount' => $response->getActualAmount(),
                    'hd_wallet_address' => $response->getAddress(),
                    'withdrawal_date' => $response->getCreatedAt(),
                    'explore_address_url' => $response->getExploreAddress(),
                ]);


            $feeCurrency = $response->getCurrencyFee();
            $baseCurrency = $bitexroomWallet->currency_symbol;

            $feeCurrencyWallet = $this->walletService->getExchangeWallet($feeCurrency);

            $baseMarket = $this->marketRepository->getMarketBySymbol($baseCurrency, 'USDT');
            $feeMarket = $this->marketRepository->getMarketBySymbol($feeCurrency, 'USDT');



            //Base Currency
            Transaction::query()->create([
                'user_id' => config('bitexroom.user_id'),
                'wallet_id' => $bitexroomWallet->id,
                'amount' => $response->getAmount(),
                'coin_price' =>  $baseMarket ? $baseMarket->activeExchangePrice->price : 1,
                'exchange_id' => $exchange->id,
                'type' => TransactionTypeEnum::ًREF_EXCHANGE,
                'subtype' => TransactionSubTypeEnum::REF_EXCHANGE_WITHDRAWAL,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => sprintf(
                    'برداشت از صرافی مرجع (%s) به مقدار %s',
                    $exchange->name,
                    formatNumberTrimZeros((float) $response->getAmount())
                ),
            ]);

            // Create fee transaction if there's a fee
            if ((float)$response->getFee() > 0) {
                Transaction::query()->create([
                    'user_id' => config('bitexroom.user_id'),
                    'wallet_id' => $feeCurrencyWallet->id,
                    'amount' => -$response->getFee(),
                    'coin_price' =>  $feeMarket ? $feeMarket->activeExchangePrice->price : 0,
                    'exchange_id' => $exchange->id,
                    'type' => TransactionTypeEnum::ًREF_EXCHANGE,
                    'subtype' => TransactionSubTypeEnum::REF_EXCHANGE_WITHDRAWAL_FEE,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => sprintf(
                        'استفاده %s به مقدار %s برای برداشت از صرافی مرجع (%s)',
                        $feeCurrency,
                        formatNumberTrimZeros((float) $response->getFee()),
                        $exchange->name
                    ),
                ]);
            }
        } catch (CoinexWithdrawalException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            $context = [
                'exchange' => isset($exchange) ? $exchange->slug : null,
                'currency' => $requestDTO->getCurrency(),
                'currency_chain' => $requestDTO->getCurrencyChain(),
                'quantity' => $requestDTO->getQuantity(),
            ];

            Log::error('Charge currency failed in ExchangeService', array_merge($context, [
                'exception_message' => $exception->getMessage(),
                'exception_code' => $exception->getCode(),
            ]));

            throw $exception;
        }

        return resolve(ChargeCurrencyResponseDTO::class)
            ->setWithdrawStatus($response->getStatus());
    }

    /**
     * Trigger a sell order in the reference exchange for an OTC order
     * that was completed without reference exchange sell
     */
    public function triggerRefExchangeSell(int $otcOrderId): TriggerRefExchangeSellResponseDTO
    {
        $otcOrder = OTCOrder::with(['market', 'exchange'])->findOrFail($otcOrderId);

        // Validate the order can be triggered
        if ($otcOrder->ref_exchange_sell_status !== RefExchangeSellStatusEnum::PENDING) {
            return resolve(TriggerRefExchangeSellResponseDTO::class)
                ->setSuccess(false)
                ->setMessage('این سفارش در وضعیت مناسب برای فروش در صرافی مرجع نیست.');
        }

        $market = $otcOrder->market;
        $exchange = $otcOrder->exchange;

        if (!$exchange) {
            return resolve(TriggerRefExchangeSellResponseDTO::class)
                ->setSuccess(false)
                ->setMessage('صرافی مرجع برای این سفارش یافت نشد.');
        }

        try {
            $asset = AssetFactory::make($exchange->slug);

            $response = $asset->placeOrder(
                resolve(BuyDTORequest::class)
                    ->setSide('sell')
                    ->setMarket($market->base_currency . $market->quote_currency)
                    ->setMarketType('SPOT')
                    ->setQuantity($otcOrder->quantity)
                    ->setOrderType('market')
                    ->setCurrency($market->base_currency)
            );

            if ($response->isDone()) {
                // Update OTC order status with description
                $otcOrder->update([
                    'ref_exchange_sell_status' => RefExchangeSellStatusEnum::COMPLETED,
                    'ref_exchange_description' => sprintf(
                        'فروش دستی توسط ادمین در تاریخ %s - Order ID: %s',
                        now()->format('Y-m-d H:i:s'),
                        $response->getOrderId()
                    ),
                ]);

                // Create exchange transaction record
                // Note: json_encode is used to match the double-encoded format expected by the model's accessor
                $otcOrder->refExchangeTransactions()->create([
                    'order_id' => $response->getOrderId(),
                    'exchange_id' => $exchange->id,
                    'market' => $response->getMarket(),
                    'currency_symbol' => $response->getCurrencySymbol(),
                    'amount' => $response->getAmount(),
                    'fee' => $response->getDiscountFee(),
                    'filled_amount' => $response->getFilledAmount(),
                    'side' => 'sell',
                    'response' => json_encode($response->getResponseBody()),
                ]);

                // Create wallet transactions like api-service does
                $systemUserId = config('bitexroom.user_id');
                $feeCurrency = $this->getFeeCurrencyForExchange($exchange->slug);

                // Get or create wallets
                $feeWallet = Wallet::firstOrCreate(
                    ['user_id' => $systemUserId, 'currency_symbol' => $feeCurrency],
                    ['balance' => 0, 'available_balance' => 0]
                );
                $quoteCurrencyWallet = Wallet::firstOrCreate(
                    ['user_id' => $systemUserId, 'currency_symbol' => $market->quote_currency],
                    ['balance' => 0, 'available_balance' => 0]
                );
                $baseCurrencyWallet = Wallet::firstOrCreate(
                    ['user_id' => $systemUserId, 'currency_symbol' => $market->base_currency],
                    ['balance' => 0, 'available_balance' => 0]
                );

                // Get fee currency price
                $feeMarket = $this->marketRepository->getMarketBySymbol($feeCurrency, 'USDT');
                $feePrice = $feeMarket ? $feeMarket->activeExchangePrice->price : 0;

                // Fee transaction
                if ((float)$response->getDiscountFee() > 0) {
                    Transaction::create([
                        'user_id' => $systemUserId,
                        'wallet_id' => $feeWallet->id,
                        'otc_order_id' => $otcOrder->id,
                        'amount' => -$response->getDiscountFee(),
                        'balance' => $feeWallet->balance - $response->getDiscountFee(),
                        'coin_price' => $feeCurrency === 'USDT' ? "1" : $feePrice,
                        'exchange_id' => $exchange->id,
                        'type' => TransactionTypeEnum::REF_EXCHANGE,
                        'subtype' => TransactionSubTypeEnum::REF_EXCHANGE_SELL_FEE,
                        'status' => TransactionStatusEnum::SUCCESS,
                        'description' => sprintf(
                            'استفاده %s به مقدار %s برای فی فروش دستی در صرافی مرجع (%s)',
                            $feeCurrency,
                            formatNumberTrimZeros((float)$response->getDiscountFee()),
                            $exchange->name
                        ),
                    ]);
                }

                // Quote currency (USDT) received transaction
                $quoteAmount = $response->getFilledValue();
                if ($feeCurrency === $market->quote_currency && (float)$response->getDiscountFee() > 0) {
                    $quoteAmount = bcsub($quoteAmount, $response->getDiscountFee(), 8);
                }

                $quoteCoinPrice = "1";
                if ($market->quote_currency !== 'USDT') {
                    $quoteMarket = $this->marketRepository->getMarketBySymbol($market->quote_currency, 'USDT');
                    $quoteCoinPrice = $quoteMarket ? $quoteMarket->activeExchangePrice->price : "0";
                }

                Transaction::create([
                    'user_id' => $systemUserId,
                    'wallet_id' => $quoteCurrencyWallet->id,
                    'otc_order_id' => $otcOrder->id,
                    'amount' => $quoteAmount,
                    'balance' => $quoteCurrencyWallet->balance + (float)$quoteAmount,
                    'coin_price' => $quoteCoinPrice,
                    'exchange_id' => $exchange->id,
                    'type' => TransactionTypeEnum::REF_EXCHANGE,
                    'subtype' => TransactionSubTypeEnum::REF_EXCHANGE_SELL,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => sprintf(
                        'دریافت %s به مقدار %s از فروش دستی در صرافی مرجع (%s)',
                        $market->quote_currency,
                        formatNumberTrimZeros((float)$quoteAmount),
                        $exchange->name
                    ),
                ]);
                $quoteCurrencyWallet->increment('balance', (float)$quoteAmount);

                // Base currency sold transaction
                Transaction::create([
                    'user_id' => $systemUserId,
                    'wallet_id' => $baseCurrencyWallet->id,
                    'otc_order_id' => $otcOrder->id,
                    'amount' => -$response->getFilledAmount(),
                    'balance' => $baseCurrencyWallet->balance - (float)$response->getFilledAmount(),
                    'coin_price' => $market->activeExchangePrice->price ?? "0",
                    'exchange_id' => $exchange->id,
                    'type' => TransactionTypeEnum::REF_EXCHANGE,
                    'subtype' => TransactionSubTypeEnum::REF_EXCHANGE_SELL,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => sprintf(
                        'فروش دستی %s به مقدار %s در صرافی مرجع (%s)',
                        $market->base_currency,
                        formatNumberTrimZeros((float)$response->getFilledAmount()),
                        $exchange->name
                    ),
                ]);
                $baseCurrencyWallet->decrement('balance', (float)$response->getFilledAmount());

                return resolve(TriggerRefExchangeSellResponseDTO::class)
                    ->setSuccess(true)
                    ->setMessage(sprintf(
                        'فروش %s %s در صرافی %s با موفقیت انجام شد.',
                        formatNumberTrimZeros((float)$otcOrder->quantity),
                        $market->base_currency,
                        $exchange->name
                    ));
            } else {
                // Determine error message first
                $errorMessage = match ($response->getSpotStatus()) {
                    SpotStatusEnum::NotEnoughBalance => 'موجودی کافی در صرافی مرجع وجود ندارد.',
                    SpotStatusEnum::AmountTooSmall => 'مقدار سفارش کمتر از حداقل مجاز است.',
                    SpotStatusEnum::PriceDifferenceTooLarge => 'اختلاف قیمت بیش از حد مجاز است.',
                    SpotStatusEnum::ConnectionLosses => 'خطا در اتصال به صرافی مرجع.',
                    default => $response->getErrorMessage() ?? 'خطا در ثبت سفارش فروش.',
                };

                // Update status to failed with the error message
                $otcOrder->update([
                    'ref_exchange_sell_status' => RefExchangeSellStatusEnum::FAILED,
                    'ref_exchange_description' => $errorMessage,
                ]);

                return resolve(TriggerRefExchangeSellResponseDTO::class)
                    ->setSuccess(false)
                    ->setMessage($errorMessage);
            }
        } catch (Throwable $exception) {
            report($exception);

            $otcOrder->update([
                'ref_exchange_sell_status' => RefExchangeSellStatusEnum::FAILED,
                'ref_exchange_description' => $exception->getMessage(),
            ]);

            return resolve(TriggerRefExchangeSellResponseDTO::class)
                ->setSuccess(false)
                ->setMessage('خطا در ارتباط با صرافی مرجع: ' . $exception->getMessage());
        }
    }

    /**
     * Get the fee currency for a specific exchange
     */
    private function getFeeCurrencyForExchange(string $exchangeName): string
    {
        return config("exchanges.{$exchangeName}.fee_currency", 'USDT');
    }
}
