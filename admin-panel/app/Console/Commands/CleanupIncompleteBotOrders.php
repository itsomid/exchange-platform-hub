<?php

namespace App\Console\Commands;

use App\Enums\SpotOrderSourceEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Models\SpotOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupIncompleteBotOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cleanup-bot-orders 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--seconds=20 : Number of seconds to look back (default: 20)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete incomplete bot orders older than 20 seconds';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $seconds = (int) $this->option('seconds');
        
        $this->info("Starting bot order cleanup process...");
        $this->info("Mode: " . ($isDryRun ? 'Preview (no actual deletion)' : 'Actual deletion'));
        $this->info("Time range: Orders older than {$seconds} seconds");
        
        // Calculate cutoff time (20 seconds ago)
        $cutoffTime = Carbon::now()->subSeconds($seconds);
        
        $this->info("Cutoff time: {$cutoffTime->format('Y-m-d H:i:s')}");
        
        // Find bot orders that are incomplete and older than specified time
        // Exclude orders that have been partially filled or have associated trades
        $query = SpotOrder::query()
            ->where('source', SpotOrderSourceEnum::BOT)
            ->whereIn('status', [SpotOrderStatusEnum::OPEN, SpotOrderStatusEnum::CANCELED])
            ->where('created_at', '<', $cutoffTime)
            ->where('filled_quantity', '0') // Only orders with no filled quantity
            ->whereDoesntHave('makerTrades') // No trades as maker
            ->whereDoesntHave('takerTrades'); // No trades as taker
            
        // Count orders
        $count = $query->count();
        
        if ($count === 0) {
            $this->info('No bot orders found for deletion.');
            return Command::SUCCESS;
        }
        
        $this->info("Found orders count: {$count}");
        
        if ($isDryRun) {
            // Show order details without deletion
            $this->info('Orders that would be deleted:');
            $this->table(
                ['ID', 'Market ID', 'User ID', 'Side', 'Type', 'Quantity', 'Price', 'Created At'],
                $query->get()->map(function ($order) {
                    return [
                        $order->id,
                        $order->market_id,
                        $order->user_id,
                        $order->side->value,
                        $order->type->value,
                        $order->quantity,
                        $order->price ?? 'N/A',
                        $order->created_at->format('Y-m-d H:i:s')
                    ];
                })->toArray()
            );
            
            $this->warn('This is preview mode. To actually delete, run the command without --dry-run.');
            return Command::SUCCESS;
        }
        
        // Confirm deletion
        if (!$this->option('silent')) {
            if (!$this->confirm("Are you sure you want to delete {$count} bot orders?")) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }
        
        // Delete orders with progress bar
        $this->info('Deleting orders...');
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        $deletedCount = 0;
        $query->chunk(100, function ($orders) use (&$deletedCount, $bar) {
            foreach ($orders as $order) {
                $order->delete();
                $deletedCount++;
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Successfully deleted {$deletedCount} bot orders.");
        return Command::SUCCESS;
    }
}