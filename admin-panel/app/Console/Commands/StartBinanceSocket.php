<?php

namespace App\Console\Commands;

use App\Services\Socket\BinanceSocketService;
use Illuminate\Console\Command;

class StartBinanceSocket extends Command
{
    protected $signature = 'binance:listen';
    protected $description = 'Start listening to Binance WebSocket for price updates';

    public function handle(BinanceSocketService $binanceSocketService)
    {
        $this->info('Starting Binance WebSocket listener...');
        $binanceSocketService->startListener();
    }
}
