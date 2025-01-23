<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Services\Exchanges\WithdrawalFee\ExchangeFactory;
use Exception;
use Illuminate\Console\Command;

class FetchWithdrawalFee extends Command
{
    protected $signature = 'exchange:fetch-withdrawal-fee {exchange}';

    protected $description = 'Fetch withdrawal fee from a specific exchange';

    public function handle(): void
    {
        $exchange = $this->argument('exchange');

        try {
            $service = ExchangeFactory::make($exchange);

            $currencies = Currency::query()->with('chains')->get();
            foreach ($currencies as $currency) {
                $feeData = $service->fetchWithdrawalFee($currency->symbol);
                foreach ($currency->chains as $chain) {
                    $this->saveWithdrawalFee($feeData, $chain);
                    $this->info("Updated withdrawal fee #{$currency->symbol} On {$chain->chain->value} network | network_fee: {$chain->network_fee}");
                }
            }
        } catch (Exception $e) {
            report($e);
            $this->error($e->getMessage());
        }
    }

    private function saveWithdrawalFee(array $feeData, CurrencyChain $chain): void
    {
        $chainSymbol = $chain->chain->value;
        $foundNetwork = array_values(array_filter($feeData['networks'], function (array $value) use ($chainSymbol) {
            return $value['network'] === $chainSymbol;
        }));

        if (count($foundNetwork)) {
            $chain->update([
                'network_fee' => $foundNetwork[0]['withdrawal_fee'],
                'withdraw_enabled' => $foundNetwork[0]['withdraw_enabled'],
                'deposit_enabled' => $foundNetwork[0]['deposit_enabled'],
            ]);
        } else {
            report("Can not fetch withdrawal fee with network $chainSymbol");
        }
    }
}
