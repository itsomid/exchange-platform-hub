<?php

namespace App\Console\Commands;

use App\Models\OTCOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DeleteZeroQuantityOtcOrders extends Command
{
    protected $signature = 'otc:delete-zero-quantity-orders
                            {--dry-run : Show matching orders and transactions without deleting}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Delete OTC orders with quantity 0 and their related transactions';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        $this->info('Looking for OTC orders with quantity = 0...');

        $orders = DB::table('otc_orders')
            ->where('quantity', 0)
            ->orderBy('id')
            ->get(['id', 'user_id', 'market_id', 'quantity', 'price', 'type', 'status', 'created_at']);

        if ($orders->isEmpty()) {
            $this->info('No zero-quantity OTC orders found.');

            return self::SUCCESS;
        }

        $orderIds = $orders->pluck('id')->all();

        $transactions = DB::table('transactions')
            ->whereIn('otc_order_id', $orderIds)
            ->orderBy('id')
            ->get(['id', 'otc_order_id', 'user_id', 'amount', 'type', 'status']);

        $exchangeTransactionsCount = 0;
        if (Schema::hasTable('exchange_transactions')) {
            $exchangeTransactionsCount = DB::table('exchange_transactions')
                ->where('orderable_type', OTCOrder::class)
                ->whereIn('orderable_id', $orderIds)
                ->count();
        }

        $lockedBalanceCount = 0;
        if (Schema::hasTable('locked_balance_details') && Schema::hasColumn('locked_balance_details', 'otc_order_id')) {
            $lockedBalanceCount = DB::table('locked_balance_details')
                ->whereIn('otc_order_id', $orderIds)
                ->count();
        }

        $this->info('Found '.$orders->count().' OTC orders');
        $this->info('Found '.$transactions->count().' related transactions');
        if ($exchangeTransactionsCount > 0) {
            $this->info('Found '.$exchangeTransactionsCount.' related exchange transactions');
        }
        if ($lockedBalanceCount > 0) {
            $this->info('Found '.$lockedBalanceCount.' related locked-balance details');
        }

        $this->table(
            ['OTC ID', 'User ID', 'Market ID', 'Quantity', 'Price', 'Type', 'Status', 'Created At'],
            $orders->map(fn ($order) => [
                $order->id,
                $order->user_id,
                $order->market_id,
                $order->quantity,
                $order->price,
                $order->type,
                $order->status,
                $order->created_at,
            ])->all()
        );

        if ($isDryRun) {
            $this->warn('DRY RUN — nothing was deleted.');

            if ($transactions->isNotEmpty()) {
                $this->info('Transactions that would be deleted:');
                $this->table(
                    ['Transaction ID', 'OTC ID', 'User ID', 'Amount', 'Type', 'Status'],
                    $transactions->take(50)->map(fn ($tx) => [
                        $tx->id,
                        $tx->otc_order_id,
                        $tx->user_id,
                        $tx->amount,
                        $tx->type,
                        $tx->status,
                    ])->all()
                );

                if ($transactions->count() > 50) {
                    $this->info('Showing first 50 of '.$transactions->count().' transactions.');
                }
            }

            return self::SUCCESS;
        }

        if (! $isForced && ! $this->confirm(
            'Delete '.$orders->count().' OTC orders and '.$transactions->count().' transactions?'
        )) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $deleted = DB::transaction(function () use ($orderIds) {
            $deletedLocked = 0;
            $deletedExchangeTx = 0;

            if (Schema::hasTable('locked_balance_details') && Schema::hasColumn('locked_balance_details', 'otc_order_id')) {
                $deletedLocked = DB::table('locked_balance_details')
                    ->whereIn('otc_order_id', $orderIds)
                    ->delete();
            }

            if (Schema::hasTable('exchange_transactions')) {
                $deletedExchangeTx = DB::table('exchange_transactions')
                    ->where('orderable_type', OTCOrder::class)
                    ->whereIn('orderable_id', $orderIds)
                    ->delete();
            }

            $deletedTransactions = DB::table('transactions')
                ->whereIn('otc_order_id', $orderIds)
                ->delete();

            $deletedOrders = DB::table('otc_orders')
                ->whereIn('id', $orderIds)
                ->where('quantity', 0)
                ->delete();

            return [
                'orders' => $deletedOrders,
                'transactions' => $deletedTransactions,
                'exchange_transactions' => $deletedExchangeTx,
                'locked_balance_details' => $deletedLocked,
            ];
        });

        $this->info("Deleted {$deleted['transactions']} transactions");
        $this->info("Deleted {$deleted['orders']} OTC orders");
        if ($deleted['exchange_transactions'] > 0) {
            $this->info("Deleted {$deleted['exchange_transactions']} exchange transactions");
        }
        if ($deleted['locked_balance_details'] > 0) {
            $this->info("Deleted {$deleted['locked_balance_details']} locked-balance details");
        }

        Log::info('Deleted zero-quantity OTC orders', [
            'otc_order_ids' => $orderIds,
            'deleted' => $deleted,
        ]);

        $this->info('Deletion completed successfully.');

        return self::SUCCESS;
    }
}
