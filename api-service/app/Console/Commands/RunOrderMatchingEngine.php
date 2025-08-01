<?php

namespace App\Console\Commands;

use App\Models\Setting;
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
        // بررسی اینکه آیا کامند فعال است یا نه
        if (!Setting::isEnabled('order_matching_enabled')) {
            $this->info('Order matching command is disabled.');
            return;
        }

        $this->info('Running order matching engine...');
        $this->orderMatchingEngine->processOrder();
        $this->info('Order matching completed.');
    }
}
