<?php

namespace App\Jobs;

use App\Events\OrderBookUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Responsibilities (English):
 * - Queue and de-duplicate Order Book broadcasts per market using a time window.
 * - Provide uniqueness via `$uniqueFor` so burst updates collapse to a single job.
 * - Generate per-market unique key with `uniqueId()` and use Redis via `uniqueVia()`
 *   to guarantee cross-worker/server uniqueness.
 * - On execution, dispatch `OrderBookUpdated` to publish the latest snapshot
 *   on channel `order-book.{marketId}`.
 * Notes:
 * - Tune `$uniqueFor` to balance UX latency versus system load (1–3 seconds typical).
 * - This job does not build the broadcast payload; the event constructs it to keep concerns separated.
 */
class BroadcastOrderBook implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $marketId;

    /**
     * Only one job per market should run within this window (seconds).
     */
    public int $uniqueFor = 1;

    public function __construct(int $marketId)
    {
        $this->marketId = $marketId;
    }

    public function uniqueId(): string
    {
        return 'broadcast-orderbook-market-' . $this->marketId;
    }

    /**
     * Use Redis for the unique lock to work reliably across workers.
     */
    public function uniqueVia()
    {
        return Cache::driver('redis');
    }

    public function handle(): void
    {
        // Dispatch a single broadcast carrying the latest snapshot
        event(new OrderBookUpdated($this->marketId));
    }
}
