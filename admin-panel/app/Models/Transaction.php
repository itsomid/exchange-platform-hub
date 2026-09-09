<?php

namespace App\Models;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Filters\Filterable;
use App\Models\Bot\BotWalletTransfer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Transaction extends Model
{
    use Filterable, HasApiTokens, HasFactory, SoftDeletes;

    public $filterNameSpace = 'App\Filters\TransactionFilter';

    protected $fillable = [
        'user_id',
        'admin_id',
        'stock_contract_id',
        'wallet_id',
        'deposit_id',
        'withdrawal_id',
        'otc_order_id',
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
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionTypeEnum::class,
            'subtype' => TransactionSubTypeEnum::class,
            'status' => TransactionStatusEnum::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function referralCodeUsage(): HasOne
    {
        return $this->hasOne(ReferralCodeUsage::class, 'transaction_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function OTCOrder()
    {
        return $this->belongsTo(OTCOrder::class, 'otc_order_id');
    }

    public function deposit()
    {
        return $this->belongsTo(Deposit::class, 'deposit_id');
    }

    public function withdrawal()
    {
        return $this->belongsTo(Deposit::class, 'withdrawal_id');
    }

    public function spotTrade()
    {
        return $this->belongsTo(SpotTrade::class, 'spot_trade_id');
    }

    public function stockContract()
    {
        return $this->belongsTo(StockContract::class, 'stock_contract_id');
    }

    public function botWalletTransfer(): BelongsTo
    {
        return $this->belongsTo(BotWalletTransfer::class);
    }

    public function sweeperTransactionLog(): BelongsTo
    {
        return $this->belongsTo(SweeperTransactionLog::class, 'sweeper_tx_id');
    }
}
