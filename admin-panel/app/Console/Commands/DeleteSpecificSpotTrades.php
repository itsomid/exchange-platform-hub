<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteSpecificSpotTrades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spot-trades:delete-specific
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete spot trades with maker_order_id=44, quantity=0, market_id=3 and their related trading commissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        $this->info('Starting deletion process for specific spot trades...');
        $this->info('Criteria: maker_order_id=44, quantity=0, market_id=3');

        try {
            // Find the spot trades that match our criteria
            $spotTrades = DB::table('spot_trades')
                ->where('maker_order_id', 44)
                ->where('quantity', 0)
                ->where('market_id', 3)
                ->get();

            if ($spotTrades->isEmpty()) {
                $this->info('No spot trades found matching the criteria.');
                return 0;
            }

            $spotTradeIds = $spotTrades->pluck('id')->toArray();
            $spotTradesCount = $spotTrades->count();

            // Find related trading commissions
            $tradingCommissions = DB::table('trading_commissions')
                ->whereIn('spot_trade_id', $spotTradeIds)
                ->get();

            $commissionsCount = $tradingCommissions->count();

            $this->info("Found {$spotTradesCount} spot trades to delete");
            $this->info("Found {$commissionsCount} related trading commissions to delete");

            if ($isDryRun) {
                $this->warn('DRY RUN MODE - No actual deletions will be performed');
                
                $this->table(
                    ['Spot Trade ID', 'Maker Order ID', 'Quantity', 'Market ID'],
                    $spotTrades->map(function ($trade) {
                        return [
                            $trade->id,
                            $trade->maker_order_id,
                            $trade->quantity,
                            $trade->market_id
                        ];
                    })->toArray()
                );

                if ($commissionsCount > 0) {
                    $this->info("Trading commissions that would be deleted:");
                    $this->table(
                        ['Commission ID', 'Spot Trade ID'],
                        $tradingCommissions->map(function ($commission) {
                            return [
                                $commission->id,
                                $commission->spot_trade_id
                            ];
                        })->toArray()
                    );
                }

                return 0;
            }

            // Confirm deletion unless forced
            if (!$isForced) {
                if (!$this->confirm("Are you sure you want to delete {$spotTradesCount} spot trades and {$commissionsCount} trading commissions?")) {
                    $this->info('Operation cancelled.');
                    return 0;
                }
            }

            // Perform the deletion in a transaction
            DB::transaction(function () use ($spotTradeIds, $commissionsCount, $spotTradesCount) {
                // Delete trading commissions first (foreign key constraint)
                if ($commissionsCount > 0) {
                    $deletedCommissions = DB::table('trading_commissions')
                        ->whereIn('spot_trade_id', $spotTradeIds)
                        ->delete();
                    
                    $this->info("Deleted {$deletedCommissions} trading commission records");
                    Log::info("Deleted {$deletedCommissions} trading commission records for spot trades", [
                        'spot_trade_ids' => $spotTradeIds
                    ]);
                }

                // Delete spot trades
                $deletedSpotTrades = DB::table('spot_trades')
                    ->where('maker_order_id', 44)
                    ->where('quantity', 0)
                    ->where('market_id', 3)
                    ->delete();

                $this->info("Deleted {$deletedSpotTrades} spot trade records");
                Log::info("Deleted {$deletedSpotTrades} spot trade records", [
                    'criteria' => [
                        'maker_order_id' => 44,
                        'quantity' => 0,
                        'market_id' => 3
                    ],
                    'deleted_ids' => $spotTradeIds
                ]);
            });

            $this->info('Deletion completed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('An error occurred during deletion: ' . $e->getMessage());
            Log::error('Error deleting specific spot trades', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}