<?php

namespace Tests\Unit\Bot;

use App\Http\Controllers\Admin\Bot\BotReportController;
use App\Models\Currency;
use ReflectionMethod;
use Tests\TestCase;

class BotReportCoinRowsTest extends TestCase
{
    public function test_coin_names_come_from_the_currency_table_even_without_a_buy_in_the_window(): void
    {
        $currency = new Currency([
            'symbol' => 'BTC',
            'name' => 'Bitcoin',
            'persian_name' => 'بیت‌کوین',
            'logo' => null,
        ]);
        $currency->id = 5;

        $rows = $this->present(
            collect(),
            collect([(object) [
                'currency_id' => '5',
                'net_pnl' => '1.5',
                'performance_fee' => '0.2',
                'cancel_fee' => '0',
            ]]),
            collect([$currency]),
        );

        $this->assertCount(1, $rows);
        $this->assertSame('BTC', $rows[0]['symbol']);
        $this->assertSame('بیت‌کوین', $rows[0]['name']);
        $this->assertSame('0', $rows[0]['bought_volume']);
        $this->assertSame('1.5', $rows[0]['net_pnl']);
    }

    private function present($execRows, $pnlRows, $currencies)
    {
        $method = new ReflectionMethod(BotReportController::class, 'presentCoinRows');

        return $method->invoke(new BotReportController, $execRows, $pnlRows, $currencies);
    }
}
