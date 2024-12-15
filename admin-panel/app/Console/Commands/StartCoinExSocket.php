<?php
namespace App\Console\Commands;

use App\Services\Socket\CoinExSocketService;
use Illuminate\Console\Command;

class StartCoinExSocket extends Command
{
    protected $signature = 'coinex:listen';
    protected $description = 'Start listening to CoinEx WebSocket for price updates';

    public function handle(CoinExSocketService $coinExSocketService)
    {
        $this->info('Starting CoinEx WebSocket listener...');
        $coinExSocketService->startListener();
    }
}
