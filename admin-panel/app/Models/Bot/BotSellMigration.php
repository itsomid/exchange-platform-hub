<?php

namespace App\Models\Bot;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Admin "re-place a coin's CoinEx sell tiers on Binance" run. Written and
 * executed by api-service (BotSellMigrationService); read-only here.
 */
class BotSellMigration extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DONE    = 'DONE';
    public const STATUS_FAILED  = 'FAILED';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'required_amount' => 'decimal:8',
            'details'         => 'array',
            'started_at'      => 'datetime',
            'finished_at'     => 'datetime',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_DONE, self::STATUS_FAILED], true);
    }

    /**
     * The run is executed by api-service's MigrateSellOrdersJob. If that job is
     * no longer queued nothing will ever finish the row, so it is marked FAILED.
     * A RUNNING row is only judged after the job's own timeout (1800s).
     */
    public function failIfOrphaned(): bool
    {
        if ($this->isFinished()) {
            return false;
        }

        [$since, $graceSeconds] = $this->status === self::STATUS_RUNNING
            ? [$this->started_at, 1800 + 120]
            : [$this->created_at, 60];

        if ($since === null || $since->gt(now()->subSeconds($graceSeconds)) || $this->isQueued()) {
            return false;
        }

        $updated = static::whereKey($this->id)
            ->where('status', $this->status)
            ->update([
                'status'      => self::STATUS_FAILED,
                'error'       => 'Job این انتقال دیگر در صف وجود ندارد و اجرا نشد.',
                'finished_at' => now(),
            ]);

        if ($updated) {
            $this->refresh();
        }

        return (bool) $updated;
    }

    private function isQueued(): bool
    {
        $needle = '"migrationId";i:' . $this->id . ';';

        return DB::table('jobs')
            ->where('payload', 'like', '%MigrateSellOrdersJob%')
            ->pluck('payload')
            ->contains(fn ($payload) => str_contains((string) (json_decode($payload, true)['data']['command'] ?? ''), $needle));
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

    /**
     * @return array<string,mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id'              => $this->id,
            'status'          => $this->status,
            'status_label'    => $this->statusLabel(),
            'finished'        => $this->isFinished(),
            'currency_symbol' => strtoupper((string) $this->currency?->symbol),
            'admin_label'     => $this->admin_label,
            'tiers_count'     => (int) $this->tiers_count,
            'placed_count'    => (int) $this->placed_count,
            'completed_count' => (int) $this->completed_count,
            'failed_count'    => (int) $this->failed_count,
            'required_amount' => (string) $this->required_amount,
            'details'         => $this->details,
            'error'           => $this->error,
            'created_at'      => $this->created_at?->toIso8601String(),
            'finished_at'     => $this->finished_at?->toIso8601String(),
        ];
    }
}
