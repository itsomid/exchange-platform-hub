<?php

namespace Tests\Unit\Bot;

use App\Services\Bot\BotSettlementQuote;
use Tests\TestCase;

class BotSettlementQuoteTest extends TestCase
{
    public function test_recovers_fill_price_rounded_back_from_truncated_gross(): void
    {
        $quote = BotSettlementQuote::fromAmounts(
            '40.72208389',
            '689.03695247',
            '0.033949',
            'percent',
            '20',
        );

        $this->assertNotNull($quote);
        $this->assertSame('0.05910000', $quote['fill_price']);
        $this->assertSame('0.04073880', $quote['target_price']);
        $this->assertSame('-0.01836120', $quote['distance_amount']);
        $this->assertSame('74.0847', $quote['move_pct']);

        $html = view('dashboard.bot.orders.partials.settlement-quote', ['quote' => $quote])->render();
        $this->assertStringContainsString('قیمت تسویه', $html);
        $this->assertStringContainsString('0.0591', $html);
        $this->assertStringContainsString('74.08', $html);
        $this->assertStringContainsString('بالاتر از هدف', $html);
    }

    public function test_measures_distance_and_progress_toward_a_percent_target(): void
    {
        $quote = BotSettlementQuote::fromAmounts('1100', '10', '100', 'percent', '20');

        $this->assertSame('110.00000000', $quote['fill_price']);
        $this->assertSame('120.00000000', $quote['target_price']);
        $this->assertSame('10.00000000', $quote['distance_amount']);
        $this->assertSame('9.0909', $quote['distance_pct']);
        $this->assertSame('50.0000', $quote['progress_pct']);
        $this->assertSame('10.0000', $quote['move_pct']);
    }

    public function test_absolute_price_target_is_not_treated_as_a_percent(): void
    {
        $quote = BotSettlementQuote::fromAmounts('1100', '10', '100', 'price', '150');

        $this->assertSame('150.00000000', $quote['target_price']);
        $this->assertSame('40.00000000', $quote['distance_amount']);
    }

    public function test_stored_fill_price_is_used_for_new_rows(): void
    {
        $quote = BotSettlementQuote::fromAmounts(
            '40.72208389',
            '689.03695248',
            '0.033949',
            'percent',
            '74.86',
            '0.05910000',
        );

        $this->assertSame('0.05910000', $quote['fill_price']);
        $this->assertSame('0.05936322', $quote['target_price']);
    }

    public function test_missing_fill_price_falls_back_to_gross_divided_by_amount(): void
    {
        $quote = BotSettlementQuote::fromAmounts(
            '40.72208389',
            '689.03695248',
            '0.033949',
            'percent',
            '74.86',
            null,
        );

        $this->assertSame('0.05910000', $quote['fill_price']);
    }

    public function test_legacy_cancel_without_a_sale_has_no_quote(): void
    {
        $this->assertNull(BotSettlementQuote::fromAmounts('0', '10', '100', 'percent', '20'));
        $this->assertNull(BotSettlementQuote::fromAmounts('100', '0', '100', 'percent', '20'));
    }
}
