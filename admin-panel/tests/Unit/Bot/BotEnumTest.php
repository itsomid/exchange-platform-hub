<?php

namespace Tests\Unit\Bot;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use PHPUnit\Framework\TestCase;

class BotEnumTest extends TestCase
{
    public function test_transaction_type_enum_has_bot_case(): void
    {
        $this->assertSame('bot', TransactionTypeEnum::BOT->value);
    }

    public function test_transaction_type_enum_bot_has_label(): void
    {
        $this->assertNotEmpty(TransactionTypeEnum::BOT->label());
    }

    public function test_transaction_type_enum_bot_has_color(): void
    {
        $this->assertNotEmpty(TransactionTypeEnum::BOT->color());
    }

    public function test_transaction_type_enum_bot_has_icon(): void
    {
        $this->assertNotEmpty(TransactionTypeEnum::BOT->icon());
    }

    /**
     * @dataProvider botSubTypeCaseProvider
     */
    public function test_transaction_sub_type_enum_has_bot_cases(string $enumCase, string $expectedValue): void
    {
        $case = TransactionSubTypeEnum::from($expectedValue);
        $this->assertSame($expectedValue, $case->value);
        $this->assertNotEmpty($case->label());
    }

    public static function botSubTypeCaseProvider(): array
    {
        return [
            ['BOT_TRANSFER_IN',    'bot_transfer_in'],
            ['BOT_TRANSFER_OUT',   'bot_transfer_out'],
            ['BOT_TRANSFER_FEE',   'bot_transfer_fee'],
            ['BOT_BUY',            'bot_buy'],
            ['BOT_SELL',           'bot_sell'],
            ['BOT_EXCHANGE_FEE',   'bot_exchange_fee'],
            ['BOT_SPREAD_FEE',     'bot_spread_fee'],
            ['BOT_PERFORMANCE_FEE','bot_performance_fee'],
            ['BOT_CANCEL_FEE',     'bot_cancel_fee'],
            ['BOT_NETWORK_FEE',    'bot_network_fee'],
        ];
    }

    public function test_all_bot_sub_types_have_colours(): void
    {
        $botCases = array_filter(
            TransactionSubTypeEnum::cases(),
            fn ($case) => str_starts_with($case->value, 'bot_')
        );

        foreach ($botCases as $case) {
            $this->assertNotEmpty($case->color(), "Missing color for {$case->value}");
        }
    }
}
