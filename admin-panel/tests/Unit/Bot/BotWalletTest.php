<?php

namespace Tests\Unit\Bot;

use App\Models\Bot\BotOrder;
use App\Models\Bot\BotWallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BotWalletTest extends TestCase
{
    // ── Relations (return-type reflection) ────────────────────────────────

    public function test_user_relation_return_type_is_belongs_to(): void
    {
        $returnType = (string) (new ReflectionMethod(BotWallet::class, 'user'))->getReturnType();
        $this->assertStringContainsString('BelongsTo', $returnType);
    }

    public function test_orders_relation_return_type_is_has_many(): void
    {
        $returnType = (string) (new ReflectionMethod(BotWallet::class, 'orders'))->getReturnType();
        $this->assertStringContainsString('HasMany', $returnType);
    }

    // ── Computed attribute ────────────────────────────────────────────────

    public function test_free_balance_is_balance_minus_locked_balance(): void
    {
        $wallet = new BotWallet();
        $wallet->setRawAttributes([
            'balance'        => '1000.00000000',
            'locked_balance' => '300.00000000',
        ]);

        $this->assertSame('700.00000000', $wallet->free_balance);
    }

    public function test_free_balance_is_zero_when_fully_locked(): void
    {
        $wallet = new BotWallet();
        $wallet->setRawAttributes([
            'balance'        => '500.00000000',
            'locked_balance' => '500.00000000',
        ]);

        $this->assertSame('0.00000000', $wallet->free_balance);
    }

    // ── Casts ─────────────────────────────────────────────────────────────

    public function test_balance_is_decimal_cast(): void
    {
        $wallet = new BotWallet();
        $wallet->setRawAttributes(['balance' => '1234.56789012']);

        $this->assertSame('1234.56789012', $wallet->balance);
    }

    public function test_all_four_balance_columns_are_in_fillable(): void
    {
        $wallet   = new BotWallet();
        $fillable = $wallet->getFillable();

        foreach (['balance', 'principal_balance', 'profit_balance', 'locked_balance'] as $col) {
            $this->assertContains($col, $fillable, "Column '{$col}' missing from fillable.");
        }
    }

    // ── Table & unique ────────────────────────────────────────────────────

    public function test_wallet_uses_bot_wallets_table(): void
    {
        $wallet = new BotWallet();
        $this->assertSame('bot_wallets', $wallet->getTable());
    }
}

