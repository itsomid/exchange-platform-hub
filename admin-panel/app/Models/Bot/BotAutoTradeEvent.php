<?php

namespace App\Models\Bot;

use App\Helpers\DateFormatter;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotAutoTradeEvent extends Model
{
    public const UPDATED_AT = null;

    public const SOURCE_USER = 'user';
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_SYSTEM = 'system';

    public const ACTOR_USER = 'user';
    public const ACTOR_ADMIN = 'admin';

    public const REASON_USER_MANUAL = 'user_manual';
    public const REASON_ADMIN_TOGGLE = 'admin_toggle';
    public const REASON_ADMIN_CANCEL_ALL = 'admin_cancel_all';
    public const REASON_ORDER_ALL_BUYS_FAILED = 'order_all_buys_failed';

    protected $fillable = [
        'user_id',
        'enabled',
        'source',
        'reason_code',
        'reason',
        'actor_type',
        'actor_id',
        'actor_label',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'meta'    => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_USER   => 'کاربر',
            self::SOURCE_ADMIN  => 'ادمین',
            self::SOURCE_SYSTEM => 'سیستم (خودکار)',
            default             => (string) $this->source,
        };
    }

    public function atDisplay(): string
    {
        return DateFormatter::convertToPersianDate($this->created_at, 'H:i %Y/%m/%d');
    }

    public function summaryLine(): string
    {
        $state = $this->enabled ? 'ربات روشن شد' : 'ربات خاموش شد';
        $by    = $this->sourceLabel();
        if ($this->actor_label) {
            $by .= ' ('.$this->actor_label.')';
        }

        return $state.' — '.$this->atDisplay().' — توسط '.$by.' — دلیل: '.$this->reason;
    }

    public function toAdminArray(): array
    {
        return [
            'enabled'      => (bool) $this->enabled,
            'source'       => $this->source,
            'source_label' => $this->sourceLabel(),
            'reason'       => $this->reason,
            'reason_code'  => $this->reason_code,
            'actor_label'  => $this->actor_label,
            'at_display'   => $this->atDisplay(),
            'summary_line' => $this->summaryLine(),
        ];
    }
}
