<?php

namespace App\Console\Commands;

use App\Repositories\ExchangeRepository;
use App\Services\Socket\BinanceSocketService;
use App\Services\Socket\CoinExSocketService;
use App\Services\Socket\MexcSocketService;
use Illuminate\Console\Command;

class StartExchangeSocket extends Command
{
    protected $signature = 'exchange:listen';
    protected $description = 'Start listening to active exchange WebSocket(s) for price updates';

    public function __construct(
        protected ExchangeRepository $exchangeRepository
    ) {
        parent::__construct();
    }

    public function handle(
        CoinExSocketService $coinExSocketService,
        MexcSocketService $mexcSocketService,
        BinanceSocketService $binanceSocketService
    ) {
        $activeExchange = $this->exchangeRepository->getActiveExchange();
        $activeExchanges = $activeExchange ? [$activeExchange->slug] : [];
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

        if (in_array('binance', $activeExchanges)) {
            $this->info('Starting Binance WebSocket listener...');
            $binanceSocketService->startListener();
            $started = true;
        }

        if (!$started) {
            $this->warn('No supported active exchange found (coinex, mexc, binance).');
        }
    }
}
