<?php

namespace App\Console\Commands;

use App\Models\Exchange;
use App\Services\Socket\CoinExSocketService;
use App\Services\Socket\MexcSocketService;
use Illuminate\Console\Command;

class StartExchangeSocket extends Command
{
    protected $signature = 'exchange:listen';
    protected $description = 'Start listening to active exchange WebSocket(s) for price updates';

    public function handle(CoinExSocketService $coinExSocketService, MexcSocketService $mexcSocketService)
    {
        $activeExchanges = Exchange::query()->where('is_active', true)->pluck('slug')->toArray();
        $started = false;

        if (in_array('coinex', $activeExchanges)) {
            $this->info('Starting CoinEx WebSocket listener...');
            $coinExSocketService->startListener();
            $started = true;
        }

        if (in_array('mexc', $activeExchanges)) {
            $this->info('Starting MEXC WebSocket listener...');
            $mexcSocketService->startListener();
            $started = true;
        }

        if (!$started) {
            $this->warn('No supported active exchange found (coinex, mexc).');
        }
    }
} 