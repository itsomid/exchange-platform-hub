<?php

namespace App\Console\Commands;

use App\Events\MarketUpdated;
use App\Models\Market;
use App\Models\SpotTrade;
use Illuminate\Console\Command;
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
        $markets = Market::query()->where('is_active', true)->get();

        $marketQueryStrings = $markets->map(function ($market) {
            return "{$market->base_currency}{$market->quote_currency}";
        })->toArray();

        $response = Http::get('https://api.coinex.com/v2/spot/ticker', [
            'market' => implode(',', $marketQueryStrings),
        ])->json();

        foreach ($response['data'] as $data) {
            $market = $markets->where('base_currency', str_replace('USDT', '', $data['market']))
                ->first();

            $volume = SpotTrade::query()
                ->where('market_id', $market->id)
                ->where('created_at', '>=', now()->subHours(24))
                ->sum('quantity');
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
    }
}
