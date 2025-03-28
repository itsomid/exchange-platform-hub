<?php

namespace App\Console\Commands;

use App\Services\Spot\OrderMatchingEngine;
use Illuminate\Console\Command;

class RunOrderMatchingEngine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:match';

    /**
     * The console command description.
     *
     * @var string
     */
    public function __construct(private OrderMatchingEngine $orderMatchingEngine)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Running order matching engine...');
        $this->orderMatchingEngine->processOrder();
        $this->info('Order matching completed.');
    }
}
