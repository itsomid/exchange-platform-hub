<?php

namespace App\Models\Bot;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Admin "cancel this coin across every bot order" run. Written and executed by
 * api-service (BotCurrencyCancelService); read-only here.
 */
class BotCurrencyCancellation extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DONE    = 'DONE';
    public const STATUS_FAILED  = 'FAILED';

    public const PRICE_MARKET_SELL = 'market_sell';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'cancel_on_exchange'    => 'boolean',
            'sell_on_exchange'      => 'boolean',
            'settlement_price'      => 'decimal:8',
            'sold_amount'           => 'decimal:8',
            'sell_exchange_fee'     => 'decimal:8',
            'total_amount'          => 'decimal:8',
            'total_principal'       => 'decimal:8',
            'total_performance_fee' => 'decimal:8',
            'total_refund'          => 'decimal:8',
            'details'               => 'array',
            'started_at'            => 'datetime',
            'finished_at'           => 'datetime',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function sellOrders(): HasMany
    {
        return $this->hasMany(BotSellOrder::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_DONE, self::STATUS_FAILED], true);
    }

    /**
     * The run is executed by api-service's CancelCurrencyPositionsJob. If that job
     * is no longer in the `jobs` table (e.g. a worker without the class consumed
     * and failed it, or the worker was killed) nothing will ever finish the row,
     * so it is marked FAILED with the queue's error when one was recorded.
     * A RUNNING row is only judged after the job's own timeout (1800s) has passed.
     */
    public function failIfOrphaned(): bool
    {
        if ($this->isFinished()) {
            return false;
        }

        [$since, $graceSeconds] = $this->status === self::STATUS_RUNNING
            ? [$this->started_at, 1800 + 120]
            : [$this->created_at, 60];

        if ($since === null || $since->gt(now()->subSeconds($graceSeconds)) || $this->queuedJobPayloads('jobs', 'payload')->isNotEmpty()) {
            return false;
        }

        $exception = $this->queuedJobPayloads('failed_jobs', 'exception')->first();
        $error = $exception !== null
            ? 'Job در صف شکست خورد: ' . mb_substr(strtok($exception, "\n"), 0, 1000)
            : 'Job این لغو دیگر در صف وجود ندارد و اجرا نشد.';

        $updated = static::whereKey($this->id)
            ->where('status', $this->status)
            ->update(['status' => self::STATUS_FAILED, 'error' => $error, 'finished_at' => now()]);

        if ($updated) {
            $this->refresh();
        }

        return (bool) $updated;
    }

    /**
     * @return \Illuminate\Support\Collection<int,string> the $column of queue rows carrying this run's job
     */
    private function queuedJobPayloads(string $table, string $column): \Illuminate\Support\Collection
    {
        $needle = '"cancellationId";i:' . $this->id . ';';

        return DB::table($table)
            ->where('payload', 'like', '%CancelCurrencyPositionsJob%')
            ->orderByDesc('id')
            ->get(['payload', $column])
            ->filter(fn ($row) => str_contains((string) (json_decode($row->payload, true)['data']['command'] ?? ''), $needle))
            ->pluck($column)
            ->values();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'در صف اجرا',
            self::STATUS_RUNNING => 'در حال اجرا',
            self::STATUS_DONE    => 'انجام شد',
            self::STATUS_FAILED  => 'ناموفق',
            default              => (string) $this->status,
        };
    }

    public function exchangeModeLabel(): string
    {
        if ($this->sell_on_exchange) {
            return 'لغو پله‌ها + فروش ارز در صرافی مرجع';
        }

        return $this->cancel_on_exchange
            ? 'فقط لغو پله‌های فروش در صرافی مرجع'
            : 'بدون ارتباط با صرافی مرجع';
    }

    /**
     * @return array<string,mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id'                    => $this->id,
            'status'                => $this->status,
            'status_label'          => $this->statusLabel(),
            'finished'              => $this->isFinished(),
            'currency_symbol'       => strtoupper((string) $this->currency?->symbol),
            'exchange_mode'         => $this->exchangeModeLabel(),
            'reason'                => $this->reason,
            'admin_label'           => $this->admin_label,
            'price_source'          => $this->price_source,
            'settlement_price'      => (string) $this->settlement_price,
            'sold_amount'           => (string) $this->sold_amount,
            'sell_exchange_fee'     => (string) $this->sell_exchange_fee,
            'sell_error'            => $this->sell_error,
            'users_count'           => (int) $this->users_count,
            'orders_count'          => (int) $this->orders_count,
            'sell_orders_count'     => (int) $this->sell_orders_count,
            'skipped_count'         => (int) $this->skipped_count,
            'total_amount'          => (string) $this->total_amount,
            'total_principal'       => (string) $this->total_principal,
            'total_performance_fee' => (string) $this->total_performance_fee,
            'total_refund'          => (string) $this->total_refund,
            'error'                 => $this->error,
            'created_at'            => $this->created_at?->toIso8601String(),
            'finished_at'           => $this->finished_at?->toIso8601String(),
        ];
    }
}
