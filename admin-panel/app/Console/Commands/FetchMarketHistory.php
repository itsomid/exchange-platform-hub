<?php

namespace App\Console\Commands;

use App\Models\Market;
use App\Models\MarketHistory;
use App\Repositories\ExchangeRepository;
use App\Services\Exchanges\ExchangeData\ExchangeDataFactory;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
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

    public function __construct(
        protected ExchangeRepository $exchangeRepository
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $period = '5m';
        $limit = 1;

        $activeExchange = $this->exchangeRepository->getActiveExchange();
        if (!$activeExchange) {
            $this->warn('No active exchange found.');
            return;
        }

        $exchange = ExchangeDataFactory::make($activeExchange->slug);
        $markets = Market::query()->where('is_active', true)->where('price_update_enabled', true)->get();
        foreach ($markets as $market) {
            $symbol = $market->base_currency . $market->quote_currency;
            try {
                $candle = $exchange->fetchHistory($symbol, $period, $limit);
            } catch (Throwable $e) {
                $this->warn("Skipped {$symbol}: {$e->getMessage()}");
                Log::warning("Market history skipped for {$symbol}: {$e->getMessage()}");
                continue;
            }

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
