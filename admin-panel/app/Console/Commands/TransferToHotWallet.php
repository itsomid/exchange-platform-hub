<?php

namespace App\Console\Commands;

use App\Enums\OTCRefExchangeWithdrawalStatusEnum;
use App\Helpers\Math;
use App\Models\Currency;
use App\Models\OTCRefExchangeWithdrawal;
use App\Models\Setting;
use App\Services\Exchanges\Asset\Enum\WithdrawStatusEnum;
use App\Services\Exchanges\DTO\ChargeUSDTRequestDTO;
use App\Services\Exchanges\ExchangeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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

    const string CACHE_KEY = 'exchange_withdrawal_period_time_last_hit';
    const string EXCHANGE_WITHDRAWAL_PERIOD_TIME = 'exchange_withdrawal_period_time';
    const string EXCHANGE_WITHDRAWAL_PERIOD_BUY = 'exchange_withdrawal_period_buy';
    const string EXCHANGE_WITHDRAWAL_BOTH_TYPE = 'exchange_withdrawal_both_type';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ((int) Setting::getSetting('exchange_withdrawal_status') === 0) {
            return;
        }
        $withdrawalType = $this->getWithdrawalType();

        if ($withdrawalType === self::EXCHANGE_WITHDRAWAL_PERIOD_TIME) {
            $this->processTimeBased();
        } elseif ($withdrawalType === self::EXCHANGE_WITHDRAWAL_PERIOD_BUY) {
            $this->processCountBased();
        }elseif ($withdrawalType === self::EXCHANGE_WITHDRAWAL_BOTH_TYPE){
            $this->processCountBased();
            $this->processTimeBased();
        }
    }

    private function getPeriodTime(): int
    {
        return (int) Setting::getSetting('exchange_withdrawal_period_time');
    }

    private function getWithdrawalType(): string
    {
        return Setting::getSetting('exchange_withdrawal_type');
    }

    private function processTimeBased(): void
    {
        $lastHit = Cache::get(self::CACHE_KEY, 0);
        if ($lastHit && $lastHit->diffInMinutes() < $this->getPeriodTime()) {
            $this->info("Remaining time to withdraw : {$lastHit->diffInMinutes()} minutes");

            return;
        }

        $currencies = Currency::query()
            ->with('chains')
            ->has('chains')
            ->get();
        foreach ($currencies as $currency) {
            $this->transferCurrency($currency);
        }

        Cache::put(self::CACHE_KEY, now(), now()->addMinutes($this->getPeriodTime()));

    }

    private function processCountBased(): void
    {
        $countBuy = Setting::getSetting('exchange_withdrawal_period_buy');

        $currencies = Currency::query()
            ->with('chains')
            ->has('chains')
            ->get();

        foreach ($currencies as $currency) {
            if (OTCRefExchangeWithdrawal::query()
                ->where('currency_id', $currency->id)
                ->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)
                ->count() >= $countBuy) {
                $this->transferCurrency($currency);
            }
        }

        if($this->getWithdrawalType() === self::EXCHANGE_WITHDRAWAL_BOTH_TYPE){
            Cache::put(self::CACHE_KEY, now(), now()->addMinutes($this->getPeriodTime()));
        }
    }

    public function transferCurrency(Currency $currency): void
    {
        $chain = $currency->chains->sortBy('min_withdraw_amount')->first();

        $pendingLists = OTCRefExchangeWithdrawal::query()
            ->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)
            ->where('currency_id', $currency->id)
            ->get();

        if ($pendingLists->count() === 0) {
            $this->info('Bitexroom does not have need to charge '.$currency->name);

            return;
        }

        $quantityNeeded = 0;
        foreach ($pendingLists as $data) {
            $quantityNeeded = Math::add($quantityNeeded, $data->transaction->amount);
        }
        $quantityNeeded = abs(formatNumber($quantityNeeded, $currency->precision));
        $this->info('Withdrawal'.$currency->symbol.' - amount: '.$quantityNeeded);
        try {
            $exchangeService = resolve(ExchangeService::class);
            $chargeFromRefExchange = $exchangeService->chargeCurrency(
                resolve(ChargeUSDTRequestDTO::class)
                    ->setCurrency($currency->symbol)
                    ->setCurrencyChain($chain->chain->value)
                    ->setQuantity($quantityNeeded)
            );

            if ($chargeFromRefExchange->getWithdrawStatus() !== WithdrawStatusEnum::FAILED) {
                OTCRefExchangeWithdrawal::query()
                    ->whereIn('id', $pendingLists->pluck('id'))
                    ->update([
                        'status' => OTCRefExchangeWithdrawalStatusEnum::COMPLETED,
                    ]);
                $this->info($currency->symbol.' Withdrawal successful. status : '.$chargeFromRefExchange->getWithdrawStatus()->value);
            } else {
                $this->error($currency->symbol.' Withdrawal failed. status : '.$chargeFromRefExchange->getWithdrawStatus()->value);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());
        }

    }
}
