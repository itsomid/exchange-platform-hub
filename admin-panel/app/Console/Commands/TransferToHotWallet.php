<?php

namespace App\Console\Commands;

use App\Enums\OTCRefExchangeWithdrawalStatusEnum;
use App\Helpers\Math;
use App\Models\Currency;
use App\Models\OTCRefExchangeWithdrawal;
use App\Models\Setting;

use App\Services\Exchanges\DTO\ChargeCurrencyRequestDTO;
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

    const string TRIGGER_TYPE_TIME = 'time_condition';
    const string TRIGGER_TYPE_COUNT = 'count_condition';

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
        } elseif ($withdrawalType === self::EXCHANGE_WITHDRAWAL_BOTH_TYPE) {
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
        $currencies = Currency::query()
            ->with('chains')
            ->has('chains')
            ->where('ref_exchange_withdrawal_enabled', true)
            ->get();

        foreach ($currencies as $currency) {
            $cacheKey = self::CACHE_KEY . '_' . $currency->id;
            $lastHit = Cache::get($cacheKey);
            $intervalMinutes = $currency->getEffectiveWithdrawalInterval();

            if ($lastHit && now()->diffInMinutes($lastHit, true) < $intervalMinutes) {
                $this->info("Remaining time to withdraw {$currency->symbol}: " . ($intervalMinutes - now()->diffInMinutes($lastHit, true)) . " minutes");
                continue;
            }

            $this->transferCurrency($currency, self::TRIGGER_TYPE_TIME);
            Cache::put($cacheKey, now(), now()->addMinutes($intervalMinutes));
        }
    }

    private function processCountBased(): void
    {
        $currencies = Currency::query()
            ->with('chains')
            ->has('chains')
            ->where('ref_exchange_withdrawal_enabled', true)
            ->get();

        foreach ($currencies as $currency) {
            $minCount = $currency->getEffectiveWithdrawalMinCount();

            if (
                OTCRefExchangeWithdrawal::query()
                ->where('currency_id', $currency->id)
                ->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)
                ->count() >= $minCount
            ) {
                $this->transferCurrency($currency, self::TRIGGER_TYPE_COUNT);
            }
        }
    }

    private function isTimeConditionMet(): bool
    {
        $lastHit = Cache::get(self::CACHE_KEY);
        $this->info("Last hit from cache: " . ($lastHit ? $lastHit->toDateTimeString() : 'null')); // Log $lastHit
        if (!$lastHit) { // First run or cache expired/cleared
            $this->info("Time condition: Cache key '" . self::CACHE_KEY . "' not found. Assuming condition met.");
            return true;
        }


        $minutesSinceLastHit = now()->diffInMinutes($lastHit, true);
        $this->info("Minutes since last hit (absolute): {$minutesSinceLastHit} ");
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
        $minCount = $currency->getEffectiveWithdrawalMinCount();
        return OTCRefExchangeWithdrawal::query()
            ->where('currency_id', $currency->id)
            ->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)
            ->count() >= $minCount;
    }

    private function processBothTypes(): void
    {
        $currencies = Currency::query()
            ->with('chains')
            ->has('chains')
            ->where('ref_exchange_withdrawal_enabled', true)
            ->get();

        foreach ($currencies as $currency) {
            $cacheKey = self::CACHE_KEY . '_' . $currency->id;
            $lastHit = Cache::get($cacheKey);
            $intervalMinutes = $currency->getEffectiveWithdrawalInterval();
            
            // Check time condition for this specific currency
            $timeConditionMet = !$lastHit || now()->diffInMinutes($lastHit, true) >= $intervalMinutes;

            if ($timeConditionMet) {
                $hasPendingForThisCurrency = OTCRefExchangeWithdrawal::query()
                    ->where('currency_id', $currency->id)
                    ->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)
                    ->exists();

                if ($hasPendingForThisCurrency) {
                    $this->info("Time condition met for {$currency->symbol}. Attempting transfer.");
                    $this->transferCurrency($currency, self::TRIGGER_TYPE_TIME);
                    Cache::put($cacheKey, now(), now()->addMinutes($intervalMinutes));
                }
            } else {
                // Check count condition
                if ($this->isCountConditionMetForCurrency($currency)) {
                    $this->info("Count condition met for {$currency->symbol} in 'both' type. Attempting transfer.");
                    $this->transferCurrency($currency, self::TRIGGER_TYPE_COUNT);
                }
            }
        }
    }

    public function transferCurrency(Currency $currency, string $triggerType): void
    {
        $chain = $currency->chains
            ->filter(function ($c) {
                return (float) $c->network_fee > 0;
            })
            ->sortBy('network_fee')
            ->first();

        $pendingLists = OTCRefExchangeWithdrawal::query()
            ->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)
            ->where('currency_id', $currency->id)
            ->get();

        if ($pendingLists->count() === 0) {
            $this->info('Bitexroom does not have need to charge ' . $currency->name);
            return;
        }

        $quantityNeeded = 0;
        foreach ($pendingLists as $data) {
            $quantityNeeded = Math::add($quantityNeeded, $data->transaction->amount);
        }

        $quantityNeeded = number_format($quantityNeeded, $currency->amount_precision, '.', '');

        try {
            $exchangeService = resolve(ExchangeService::class);

            $chargeFromRefExchange = $exchangeService->chargeCurrency(
                resolve(ChargeCurrencyRequestDTO::class)
                    ->setCurrency($currency->symbol)
                    ->setCurrencyChain($chain->chain->value)
                    ->setQuantity($quantityNeeded)
            );

            if ($chargeFromRefExchange->getWithdrawStatus() !== 'failed') {
                OTCRefExchangeWithdrawal::query()
                    ->whereIn('id', $pendingLists->pluck('id'))
                    ->update([
                        'status' => OTCRefExchangeWithdrawalStatusEnum::COMPLETED,
                    ]);
                $this->info($currency->symbol . ' Withdrawal successful. status : ' . $chargeFromRefExchange->getWithdrawStatus());
                Log::channel('ref-exchange')->info(
                    "Assets Gathering for {$currency->symbol} triggered by {$triggerType}. Amount: {$quantityNeeded}. Status: {$chargeFromRefExchange->getWithdrawStatus()}"
                );
            } else {
                $this->error($currency->symbol . ' Withdrawal failed. status : ' . $chargeFromRefExchange->getWithdrawStatus());
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());
        }
    }
}
