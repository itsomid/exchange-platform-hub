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
            $this->processBothTypes(); // Changed from processCountBased() then processTimeBased()
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

    }

    private function isTimeConditionMet(): bool
    {
        $lastHit = Cache::get(self::CACHE_KEY);
        if (!$lastHit) { // First run or cache expired/cleared
            $this->info("Time condition: Cache key '" . self::CACHE_KEY . "' not found. Assuming condition met.");
            return true;
        }

        $minutesSinceLastHit = now()->diffInMinutes($lastHit);
        $periodTime = $this->getPeriodTime();

        if ($minutesSinceLastHit >= $periodTime) {
            $this->info("Time condition met: {$minutesSinceLastHit} minutes passed (>= {$periodTime} min period).");
            return true;
        } else {
            $remainingMinutes = $periodTime - $minutesSinceLastHit;
            $this->info("Time condition NOT met. {$remainingMinutes} minutes remaining in period.");
            return false;
        }
    }

    private function isCountConditionMetForCurrency(Currency $currency): bool
    {
        $countBuy = (int) Setting::getSetting('exchange_withdrawal_period_buy');
        return OTCRefExchangeWithdrawal::query()
            ->where('currency_id', $currency->id)
            ->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)
            ->count() >= $countBuy;
    }

    private function processBothTypes(): void
    {
        $currencies = Currency::query()->with('chains')->has('chains')->get();
        $timeConditionIsMet = $this->isTimeConditionMet();

        if ($timeConditionIsMet) {
            $this->info("Processing 'both' type: Time condition met. Attempting transfer for all eligible currencies.");
            $processedThisRun = false;
            foreach ($currencies as $currency) {
                $hasPendingForThisCurrency = OTCRefExchangeWithdrawal::query()
                    ->where('currency_id', $currency->id)
                    ->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)
                    ->exists();

                if ($hasPendingForThisCurrency) {
                    $this->transferCurrency($currency);
                    $processedThisRun = true;
                }
            }
            if ($processedThisRun) {
                Cache::put(self::CACHE_KEY, now(), now()->addMinutes($this->getPeriodTime()));
                $this->info("Time-based cache updated for 'both' type after processing due to time condition.");
            } else {
                $this->info("Time condition met for 'both' type, but no pending withdrawals found for any currency. Cache not updated.");
            }
        } else {
            $this->info("Processing 'both' type: Time condition NOT met. Checking count-based conditions for each currency.");
            foreach ($currencies as $currency) {
                if ($this->isCountConditionMetForCurrency($currency)) {
                    $this->info("Count condition met for {$currency->symbol} in 'both' type. Attempting transfer.");
                    $this->transferCurrency($currency);
                }
            }
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
        Log::channel('ref-exchange')->info('Withdrawal '.$currency->symbol.' - amount: '.$quantityNeeded);

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
