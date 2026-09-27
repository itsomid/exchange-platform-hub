<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Repositories\ExchangeRepository;
use App\Services\Exchanges\ExchangeData\ExchangeDataFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchDepositWithdrawalConfig extends Command
{
    protected $signature = 'exchange:fetch-deposit-withdrawal-config';

    protected $description = 'Fetch withdrawal fee from the active exchange';

    public function __construct(
        protected ExchangeRepository $exchangeRepository
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $activeExchange = $this->exchangeRepository->getActiveExchange();
        if (!$activeExchange) {
            $this->warn('No active exchange found.');
            return;
        }

        $service = ExchangeDataFactory::make($activeExchange->slug);

        $currencies = Currency::query()
            ->with(['chains' => function ($query) {
                $query->where('withdraw_enabled', true);
            }])
            ->whereHas('chains', function ($query) {
                $query->where('withdraw_enabled', true);
            })
            ->where('is_active', true)
            ->get();

        foreach ($currencies as $currency) {
            try {
                $feeData = $service->fetchWithdrawalFee($currency->symbol);
            } catch (Throwable $e) {
                $this->skip("Skipped {$currency->symbol}: {$e->getMessage()}");
                continue;
            }

            foreach ($currency->chains as $chain) {
                try {
                    if ($this->saveWithdrawalFee($feeData, $chain)) {
                        $this->info("Updated withdrawal fee #{$currency->symbol} On {$chain->chain->value} network | network_fee: {$chain->network_fee}");
                    }
                } catch (Throwable $e) {
                    $this->skip("Skipped {$currency->symbol} on {$chain->chain->value}: {$e->getMessage()}");
                }
            }
        }
    }

    private function saveWithdrawalFee(array $feeData, CurrencyChain $chain): bool
    {
        $chainSymbol = $chain->chain->value;

        $matchedNetwork = array_values(array_filter($feeData['networks'], function (array $value) use ($chainSymbol) {
            return $value['network'] === $chainSymbol;
        }));
        if (!count($matchedNetwork)) {
            $this->skip("Skipped {$feeData['currency']} on {$chainSymbol}: network not supported by exchange");
            return false;
        }

        if (!$matchedNetwork[0]['withdraw_enabled']) {
            $this->skip("Skipped {$feeData['currency']} on {$chainSymbol}: withdraw is disabled on exchange");
            return false;
        }

        $foundNetwork = $matchedNetwork[0];

        if ($foundNetwork) {
            $chain->update([
                'network_fee' => $foundNetwork['withdrawal_fee'],
                'safe_confirmations' => $foundNetwork['safe_confirmations'],
                'deposit_enabled' => $foundNetwork['deposit_enabled'],
                'withdraw_enabled' => $foundNetwork['withdraw_enabled'],
            ]);
            return true;
        }

        return false;
    }

    private function skip(string $message): void
    {
        $this->warn($message);
        Log::warning("Withdrawal config: {$message}");
    }
}
