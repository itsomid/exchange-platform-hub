<?php

namespace App\Console\Commands;

use App\Models\Market;
use App\Models\Setting;
use App\Services\Exchanges\WithdrawalFee\ExchangeFactory;
use Illuminate\Console\Command;

class FetchMinOTCAmount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange:fetch-min-otc-amount {exchange}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the minimum trade amount from CoinEx daily';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $exchange = $this->argument('exchange');
        $service = ExchangeFactory::make($exchange);
        $fetchMarkets = collect($service->fetchMinTrade())->keyBy('base_ccy');

        $markets = Market::query()->get();

        foreach ($markets as $market){
            $minAmount = $fetchMarkets[$market->base_currency]['min_amount'];
            $market->update([
                'min_otc_amount' => $minAmount
            ]);
            $this->info("Update min_otc_amount {$market->base_currency} : {$minAmount}");
        }

    }
}
