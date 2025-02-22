<?php

namespace App\Console\Commands;

use App\Enums\OTCRefExchangeWithdrawalStatusEnum;
use App\Exceptions\Coinex\CoinexWithdrawalException;
use App\Helpers\Math;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\ExchangeTransaction;
use App\Models\OTCRefExchangeWithdrawal;
use App\Models\Setting;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use App\Services\Exchanges\Asset\Enum\WithdrawStatusEnum;
use App\Services\Exchanges\DTO\ChargeUSDTRequestDTO;
use App\Services\Exchanges\ExchangeService;
use App\Services\Wallet\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransferToHotWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitexroom:transfer-to-hot-wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct(private readonly WalletService $walletService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ((int) Setting::getSetting('exchange_withdrawal_status') === 0) {
            return;
        }
        $withdrawalType = Setting::getSetting('exchange_withdrawal_type');

        if ($withdrawalType === 'exchange_withdrawal_period_time') {
            $this->processTimeBased();
        } elseif ($withdrawalType === 'exchange_withdrawal_period_buy') {
            $this->processCountBased();
        }
    }

    private function processTimeBased(): void
    {
        $periodTime = Setting::getSetting('exchange_withdrawal_period_time');
        $cacheKey = 'exchange_withdrawal_period_time_last_hit';
        $lastHit = Cache::get($cacheKey, 0);
        if ($lastHit && $lastHit->diffInMinutes() < $periodTime) {
            $this->info("Remaining time to withdraw : {$lastHit->diffInMinutes()} minutes");

            return;
        }

        $this->transferUSDT();
        $this->transferCoins();

        Cache::put($cacheKey, now(), now()->addMinutes((int) $periodTime));

    }

    private function processCountBased(): void
    {
        $countBuy = Setting::getSetting('exchange_withdrawal_period_buy');

        if (OTCRefExchangeWithdrawal::query()->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)->count() >= $countBuy) {
            $this->transferUSDT();
        }

        $refExchangeTransactionCount = ExchangeTransaction::query()
            ->whereBetween('created_at', [now()->subHour(), now()])
            ->count();

        if ($refExchangeTransactionCount < $countBuy) {
            return;
        }
        $this->transferCoins();
    }

    private function getBalance(): array
    {
        $asset = AssetFactory::make('coinex');

        $exchangeBalance = [];
        foreach ($asset->getBalance() as $balance) {
            $exchangeBalance[$balance->getCcy()] = $balance->getAvailable();
        }

        return $exchangeBalance;
    }

    public function transferUSDT(): void
    {
        $currency = Currency::query()->where('symbol', 'USDT')->first();
        $chain = CurrencyChain::query()
            ->where('chain', 'BSC')
            ->where('currency_id', $currency->id)
            ->first();

        $pendingLists = OTCRefExchangeWithdrawal::query()
            ->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)
            ->get();
        if ($pendingLists->count() === 0) {
            $this->info('Bitexroom does not have need to charge USDT.');

            return;
        }

        $usdtNeeded = 0;
        foreach ($pendingLists as $data) {
            $usdtNeeded = Math::add($usdtNeeded, $data->transaction->amount);
        }
        $usdtNeeded = abs($usdtNeeded);
        try {
            $exchangeService = resolve(ExchangeService::class);
            $chargeFromRefExchange = $exchangeService->chargeUSDT(
                resolve(ChargeUSDTRequestDTO::class)
                    ->setCurrencyChain($chain->chain->value)
                    ->setQuantity($usdtNeeded)
            );

            if ($chargeFromRefExchange->getWithdrawStatus() !== WithdrawStatusEnum::FAILED) {
                OTCRefExchangeWithdrawal::query()
                    ->whereIn('id', $pendingLists->pluck('id'))
                    ->update([
                        'status' => OTCRefExchangeWithdrawalStatusEnum::COMPLETED,
                    ]);
                $this->info('USDT Withdrawal successful. status : '.$chargeFromRefExchange->getWithdrawStatus()->value);
            } else {
                $this->error('USDT Withdrawal failed. status : '.$chargeFromRefExchange->getWithdrawStatus()->value);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());
        }

    }

    public function transferCoins(): void
    {
        $currenciesBalanceInRefExchange = $this->getBalance();

        $currencies = Currency::query()
            ->with('chains')
            ->where('symbol', 'BNB')
            ->get();

        $bitexroomChains = $this->walletService->getExchangeAllWalletChain();
        $addresses = [
            'BTC' => $bitexroomChains->where('currency_chain', 'BTC')->first()->address,
            'BNB' => $bitexroomChains->where('currency_chain', 'BSC')->first()->address,
            'DOGE' => $bitexroomChains->where('currency_chain', 'DOGE')->first()->address,
            'TRX' => $bitexroomChains->where('currency_chain', 'TRC20')->first()->address,
            'ETH' => $bitexroomChains->where('currency_chain', 'ERC20')->first()->address,
        ];

        foreach ($currencies as $currency) {
            //If balance is zero
            if(array_key_exists($currency->symbol, $currenciesBalanceInRefExchange)) {
                continue;
            }
            $amountForWithdraw = $currenciesBalanceInRefExchange[$currency->symbol];
            $destinationAddress = $addresses[$currency->symbol];
            $chain = $currency->chains->sortBy('min_withdraw_amount')->first();

            $asset = AssetFactory::make('coinex');
            try {
                $res = $asset->withdraw(
                    resolve(WithdrawRequestDTO::class)
                        ->setCurrency($currency->symbol)
                        ->setChain($chain->chain->value)
                        ->setAmount($amountForWithdraw)
                        ->setWithdrawMethod(WithdrawMethodEnum::ON_CHAIN)
                        ->setAddress($destinationAddress)
                );
                ExchangeAssetsWithdrawal::query()
                    ->create([
                        'admin_id' => null,
                        'withdrawal_id' => $res->getWithdrawId(),
                        'exchange' => 'coinex',
                        'currency_symbol' => $currency->symbol,
                        'currency_chain' => $chain->chain,
                        'fee_currency' => $res->getCurrencyFee(),
                        'fee' => $res->getFee(),
                        'amount' => $res->getAmount(),
                        'actual_amount' => $res->getActualAmount(),
                        'hd_wallet_address' => $res->getAddress(),
                        'withdrawal_date' => $res->getCreatedAt(),
                        'explore_address_url' => $res->getExploreAddress(),
                        'description' => 'Withdraw in command line',
                    ]);
            } catch (CoinexWithdrawalException $exception) {
                report($exception);
                Log::channel('ref-exchange')->info($currency->symbol.' network:'.$chain->chain->value.' amount:'.$amountForWithdraw);
                $this->error("Withdraw {$currency->symbol} is failed with amount: ".$amountForWithdraw);
            }
        }
    }
}
