<?php

namespace App\Console\Commands;

use App\Events\MarketUpdated;
use App\Models\Market;
use App\Models\Setting;
use App\Models\SpotTrade;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SpotTicker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spot:spot-ticker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch currency price updates to the socket';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // بررسی اینکه آیا کامند فعال است یا نه
        if (!Setting::isEnabled('spot_ticker_enabled')) {
            $this->info('Spot ticker command is disabled.');
            return;
        }

        $this->info('Running spot ticker...');


        $markets = Market::query()
            ->where('is_active', true)
            ->select(['id', 'base_currency', 'quote_currency'])
            ->get();

        $marketQueryStrings = $markets->map(function ($market) {
            return "{$market->base_currency}{$market->quote_currency}";
        })->toArray();

        $response = Http::get('https://api.coinex.com/v2/spot/ticker', [
            'market' => implode(',', $marketQueryStrings),
        ])->json();

        $marketsByTicker = $markets->keyBy(fn ($m) => $m->base_currency . $m->quote_currency);

        $since = now()->subHours(24);

        $marketIds = $markets->pluck('id');

        $volumes = SpotTrade::query()
            ->whereIn('market_id', $marketIds)
            ->where('created_at', '>=', $since)
            ->select('market_id', DB::raw('SUM(quantity) as volume'))
            ->groupBy('market_id')
            ->pluck('volume', 'market_id');

        foreach ($response['data'] as $data) {

            $market = $marketsByTicker->get($data['market']);
            if (!$market) {
                continue;
            }

            $volume = (float)($volumes[$market->id] ?? 0);
            //Dispatch price via socket to the frontend. use Laravel Reverb
            MarketUpdated::dispatch($market->id, [
                'low' => $data['low'],
                'high' => $data['high'],
                'volume' => $volume,
                'last' => $data['last'],
                'open' => $data['open'],
                'price_change_percentage' => round((($data['last'] - $data['open']) / $data['open']) * 100, 2),
            ]);
        }

        $this->info('Spot ticker completed successfully.');
    }
}
