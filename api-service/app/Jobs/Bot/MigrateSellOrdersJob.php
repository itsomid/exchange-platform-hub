<?php

namespace App\Jobs\Bot;

use App\Models\Bot\BotSellMigration;
use App\Services\Bot\BotSellMigrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs one admin "re-place this coin's CoinEx sell tiers on Binance" request
 * (see BotSellMigrationService). Places real exchange orders, so it is never
 * retried: the service claims the row PENDING → RUNNING and a second delivery
 * is a no-op.
 */
class MigrateSellOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 1800;

    public function __construct(public readonly int $migrationId) {}

    public function handle(BotSellMigrationService $service): void
    {
        $service->run($this->migrationId);
    }

    public function failed(?\Throwable $e): void
    {
        BotSellMigration::whereKey($this->migrationId)
            ->where('status', BotSellMigration::STATUS_PENDING)
            ->update([
                'status'      => BotSellMigration::STATUS_FAILED,
                'error'       => $e?->getMessage(),
                'finished_at' => now(),
            ]);
    }
}
