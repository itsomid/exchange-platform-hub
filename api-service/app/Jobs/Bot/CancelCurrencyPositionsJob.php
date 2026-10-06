<?php

namespace App\Jobs\Bot;

use App\Models\Bot\BotCurrencyCancellation;
use App\Services\Bot\BotCurrencyCancelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs one admin "cancel this coin" request (see BotCurrencyCancelService).
 * Moves money and places real exchange orders, so it is never retried: the
 * service claims the row PENDING → RUNNING and a second delivery is a no-op.
 */
class CancelCurrencyPositionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 1800;

    public function __construct(public readonly int $cancellationId) {}

    public function handle(BotCurrencyCancelService $service): void
    {
        $service->run($this->cancellationId);
    }

    public function failed(?\Throwable $e): void
    {
        // Only a run that never started is marked failed here; a RUNNING row
        // may still be in progress on another delivery.
        BotCurrencyCancellation::whereKey($this->cancellationId)
            ->where('status', BotCurrencyCancellation::STATUS_PENDING)
            ->update([
                'status'      => BotCurrencyCancellation::STATUS_FAILED,
                'error'       => $e?->getMessage(),
                'finished_at' => now(),
            ]);
    }
}
