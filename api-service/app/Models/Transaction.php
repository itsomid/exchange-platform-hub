<?php

namespace App\Models;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Bot\BotWalletTransfer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int                   $id
 * @property string                $amount
 * @property TransactionStatusEnum $status
 * @property TransactionTypeEnum   $type
 */
class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'admin_id',
        'wallet_id',
        'deposit_id',
        'withdrawal_id',
        'otc_order_id',
        'spot_trade_id',
        'stock_contract_id',
        'bot_order_id',
        'bot_buy_execution_id',
        'bot_wallet_transfer_id',
        'sweeper_tx_id',
        'amount',
        'balance',
        'coin_price',
        'exchange_id',
        'type',
        'subtype',
        'description',
        'admin_description',
        'status',
        'journal_entry_number',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionTypeEnum::class,
            'subtype' => TransactionSubTypeEnum::class,
            'status' => TransactionStatusEnum::class,
        ];
    }

    public function deposit(): HasOne
    {
        return $this->hasOne(Deposit::class, 'id', 'deposit_id');
    }

    public function withdrawal(): HasOne
    {
        return $this->hasOne(Withdrawal::class, 'id', 'withdrawal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
    public function OTCOrder(): BelongsTo
    {
        return $this->belongsTo(OTCOrder::class, 'otc_order_id');
    }
    public function spotTrade(): BelongsTo
    {
        return $this->belongsTo(SpotTrade::class, 'spot_trade_id');
    }

    public function stockContract(): BelongsTo
    {
        return $this->belongsTo(StockContract::class, 'stock_contract_id');
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class, 'exchange_id');
    }

    public function botWalletTransfer(): BelongsTo
    {
        return $this->belongsTo(BotWalletTransfer::class);
    }
}
