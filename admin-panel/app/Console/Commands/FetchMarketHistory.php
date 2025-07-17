<?php

namespace App\Console\Commands;

use App\Models\Market;
use App\Models\MarketHistory;
use App\Services\Exchanges\MarketHistory\MarketHistoryFactory;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

class FetchMarketHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:market-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch market history data from the exchange';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $period = '5min';       // Time interval, e.g., 1m, 5m, 1h, 1d
        $limit = 1;     // Number of candles to fetch
        $exchange = 'coinex'; // Exchange name (e.g., coinex, binance)


        $exchange = MarketHistoryFactory::make($exchange);
        $markets = Market::query()->where('is_active', true)->where('price_update_enabled', true)->get();
        foreach ($markets as $market) {
            $candle = $exchange->fetchHistory($market->base_currency . $market->quote_currency, $period, $limit);
            if($this->saveMarketHistory($market, $candle[0])){
                $this->info('Market history for ' . $market->base_currency . ' fetched successfully.');
            }
        }

        //Truncate the table to keep only the last 7 days of data
        MarketHistory::query()->where('timestamp', '<', now()->subDays(8))->delete();

    }

    /**
     * @param Market $market
     * @param array $candle
     * @return bool
     */
    public function saveMarketHistory(Market $market, array $candle): bool
    {
        try {
            MarketHistory::query()
                ->create([
                    'market_id' => $market->id,
                    'open' => $candle['open'],
                    'high' => $candle['high'],
                    'low' => $candle['low'],
                    'close' => $candle['close'],
                    'volume' => $candle['volume'],
                    'timestamp' => $candle['timestamp'],
                ]);
            return true;
        } catch (UniqueConstraintViolationException $e) {
            report($e);
            $this->info("Market history for {$market->base_currency} already exists.");
        } catch (Throwable $e) {
            report($e);
        }
        return false;
    }
}
