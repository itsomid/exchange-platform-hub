<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Services\Exchanges\WithdrawalFee\ExchangeFactory;
use Exception;
use Illuminate\Console\Command;

class FetchDepositWithdrawalConfig extends Command
{
    protected $signature = 'exchange:fetch-deposit-withdrawal-config {exchange}';

    protected $description = 'Fetch withdrawal fee from a specific exchange';

    public function handle(): void
    {
        $exchange = $this->argument('exchange');

        try {
            $service = ExchangeFactory::make($exchange);

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

                $feeData = $service->fetchWithdrawalFee($currency->symbol);

                foreach ($currency->chains as $chain) {

                    if ($this->saveWithdrawalFee($feeData, $chain)) {
                        $this->info("Updated withdrawal fee #{$currency->symbol} On {$chain->chain->value} network | network_fee: {$chain->network_fee}");
                    }
                }
            }
        } catch (Exception $e) {
            report($e);
            $this->error($e->getMessage());
        }
    }

    private function saveWithdrawalFee(array $feeData, CurrencyChain $chain): bool
    {
        $chainSymbol = $chain->chain->value;

        $matchedNetwork = array_values(array_filter($feeData['networks'], function (array $value) use ($chainSymbol) {
            return $value['network'] === $chainSymbol;
        }));
        if (!count($matchedNetwork)) {
            report("Can not fetch withdrawal fee for currency {$feeData['currency']} with network {$chainSymbol}: network not returned by exchange");
            return false;
        }

        if (!$matchedNetwork[0]['withdraw_enabled']) {
            report("Can not fetch withdrawal fee for currency {$feeData['currency']} with network {$chainSymbol}: withdraw is disabled on exchange");
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
}
