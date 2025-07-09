<?php

namespace App\Console\Commands;

use App\Services\Socket\MexcSocketService;
use Illuminate\Console\Command;

class StartMexcSocket extends Command
{
    protected $signature = 'mexc:listen';
    protected $description = 'Start listening to MEXC WebSocket for price updates';

    public function handle(MexcSocketService $mexcSocketService)
    {
        $this->info('Starting MEXC WebSocket listener...');
        $mexcSocketService->startListener();
    }
}