<?php

namespace App\Models\Bot;

use App\Helpers\DateFormatter;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read model for the audit trail api-service writes on every attempt to deploy
 * a user's free bot balance. Backs the "why wasn't this balance bought?" panel
 * on the user's bot page.
 */
class BotBuyAttempt extends Model
{
    public const UPDATED_AT = null;

    public const OUTCOME_ORDER_CREATED = 'ORDER_CREATED';
    public const OUTCOME_BLOCKED       = 'BLOCKED';
    public const OUTCOME_EXCEPTION     = 'EXCEPTION';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'details'         => 'array',
            'balance'         => 'decimal:8',
            'locked_balance'  => 'decimal:8',
            'free_balance'    => 'decimal:8',
            'gate_amount'     => 'decimal:8',
            'total_allocated' => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function botOrder(): BelongsTo
    {
        return $this->belongsTo(BotOrder::class);
    }

    public function triggerLabel(): string
    {
        return match ($this->triggered_by) {
            'TRANSFER_IN' => 'واریز به کیف پول ربات',
            'TOGGLE_ON'   => 'روشن شدن ربات',
            'REINVEST'    => 'آزاد شدن پول پس از پر شدن پله فروش',
            'SIGNAL_SCAN' => 'اسکن خودکار سیگنال‌ها',
            'ADMIN_BUY'   => 'خرید دستی توسط ادمین',
            'MANUAL'      => 'اجرای دستی',
            default       => (string) $this->triggered_by,
        };
    }

    public function outcomeLabel(): string
    {
        return match ($this->outcome) {
            self::OUTCOME_ORDER_CREATED => 'سفارش خرید ثبت شد',
            self::OUTCOME_BLOCKED       => 'خریدی انجام نشد',
            self::OUTCOME_EXCEPTION     => 'خطای سیستمی',
            default                     => (string) $this->outcome,
        };
    }

    /**
     * Short label for the reason column; the full sentence lives in
     * `reason_message`, written by api-service at the moment of the attempt.
     */
    public function reasonLabel(): string
    {
        return match ($this->reason_code) {
            'auto_trade_disabled'       => 'ربات کاربر خاموش بود',
            'bot_globally_disabled'     => 'ربات سراسری غیرفعال بود',
            'no_bot_wallet'             => 'کیف پول ربات وجود نداشت',
            'insufficient_free_balance' => 'موجودی آزاد کمتر از حداقل لازم',
            'no_eligible_signals'       => 'هیچ سیگنالی در بازه قیمتی نبود',
            'no_buyable_allocation'     => 'سهم هیچ ارزی به حداقل خرید نرسید',
            null, ''                    => '—',
            default                     => (string) $this->reason_code,
        };
    }

    public function atDisplay(): string
    {
        return DateFormatter::convertToPersianDate($this->created_at, 'H:i:s %Y/%m/%d');
    }

    public function toAdminArray(): array
    {
        $details = is_array($this->details) ? $this->details : [];

        return [
            'id'                 => (int) $this->id,
            'at_display'         => $this->atDisplay(),
            'triggered_by'       => (string) $this->triggered_by,
            'trigger_label'      => $this->triggerLabel(),
            'outcome'            => (string) $this->outcome,
            'outcome_label'      => $this->outcomeLabel(),
            'reason_code'        => $this->reason_code,
            'reason_label'       => $this->reasonLabel(),
            'reason_message'     => $this->reason_message,
            'bot_order_id'       => $this->bot_order_id ? (int) $this->bot_order_id : null,
            'bot_sell_order_id'  => $this->bot_sell_order_id ? (int) $this->bot_sell_order_id : null,
            'balance'            => (string) $this->balance,
            'locked_balance'     => (string) $this->locked_balance,
            'free_balance'       => (string) $this->free_balance,
            'gate_amount'        => $this->gate_amount === null ? null : (string) $this->gate_amount,
            'gate_kind'          => $this->gate_kind,
            'total_allocated'    => (string) $this->total_allocated,
            'allocated_count'    => (int) $this->allocated_count,
            'skipped_count'      => (int) $this->skipped_count,
            'out_of_range_count' => (int) $this->out_of_range_count,
            'unpriced_count'     => (int) $this->unpriced_count,
            'exception'          => $this->exception,
            'allocations'        => $details['allocations'] ?? [],
            'skipped'            => $details['skipped'] ?? [],
            'out_of_range'       => $details['out_of_range'] ?? [],
            'unpriced'           => $details['unpriced'] ?? [],
        ];
    }
}
