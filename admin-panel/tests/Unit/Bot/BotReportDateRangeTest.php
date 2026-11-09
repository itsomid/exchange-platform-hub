<?php

namespace Tests\Unit\Bot;

use App\Http\Controllers\Admin\Bot\BotReportController;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use ReflectionMethod;
use Tests\TestCase;

class BotReportDateRangeTest extends TestCase
{
    public function test_empty_request_covers_the_last_seven_days(): void
    {
        [$from, $to, $currencyId, $fromInput, $toInput, $defaultWeek] = $this->filters(Request::create('/admin/auto-trade/reports', 'GET'));

        $this->assertNull($currencyId);
        $this->assertTrue($defaultWeek);
        $this->assertSame(now()->subDays(6)->toDateString(), $from->toDateString());
        $this->assertSame('00:00:00', $from->format('H:i:s'));
        $this->assertSame(now()->toDateString(), $to->toDateString());
        $this->assertSame('23:59:59', $to->format('H:i:s'));
        $this->assertSame(Jalalian::fromCarbon($from)->format('Y/m/d'), $fromInput);
        $this->assertSame(Jalalian::fromCarbon($to)->format('Y/m/d'), $toInput);
    }

    public function test_jalali_dates_bound_the_whole_selected_days(): void
    {
        $day = Jalalian::fromCarbon(now()->subDays(2))->format('Y/m/d');

        [$from, $to, , , , $defaultWeek] = $this->filters(Request::create('/admin/auto-trade/reports', 'GET', [
            'from' => $day,
            'to' => $day,
        ]));

        $this->assertFalse($defaultWeek);
        $this->assertSame($day, Jalalian::fromCarbon($from)->format('Y/m/d'));
        $this->assertSame($day, Jalalian::fromCarbon($to)->format('Y/m/d'));
        $this->assertSame('00:00:00', $from->format('H:i:s'));
        $this->assertSame('23:59:59', $to->format('H:i:s'));
    }

    private function filters(Request $request): array
    {
        $method = new ReflectionMethod(BotReportController::class, 'filters');

        return $method->invoke(new BotReportController, $request);
    }
}
