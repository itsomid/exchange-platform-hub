<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BotCurrencyExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(protected Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'نماد',
            'نام ارز',
            'وضعیت سیگنال',
            'کاربران کل',
            'کاربران باز',
            'سفارش‌ها کل',
            'سفارش‌ها باز',
            'تخصیص کل (USDT)',
            'مقدار خریده‌شده',
            'میانگین خرید',
            'قیمت لحظه‌ای',
            'پله‌های فروش باز',
            'پله‌های فروش پر',
            'پله‌های فروش لغو',
            'موقعیت باز — مقدار',
            'موقعیت باز — اصل سرمایه (USDT)',
            'ارزش لحظه‌ای باز (USDT)',
            'سود/زیان باز (USDT)',
            'سود محقق‌شده (USDT)',
            'کارمزد عملکرد (USDT)',
            'خرید در حال انجام',
            'وضعیت عملیات',
        ];
    }

    public function map($row): array
    {
        $hasOpen = $row->open_tiers > 0;

        if ($row->signal_active === true) {
            $signal = 'فعال';
        } elseif ($row->signal_active === false) {
            $signal = 'غیرفعال';
        } else {
            $signal = 'بدون سیگنال';
        }

        if ($row->running) {
            $status = 'لغو #' . $row->running->id . ' — ' . $row->running->statusLabel();
        } elseif ($hasOpen) {
            $status = 'قابل لغو';
        } else {
            $status = 'بسته';
        }

        return [
            $row->symbol,
            $row->currency->persian_name ?: $row->currency->name,
            $signal,
            $row->users_count,
            $row->open_users,
            $row->orders_count,
            $row->open_orders,
            $this->num($row->total_allocated, 2),
            $this->num($row->total_bought, 8),
            $row->avg_buy_price !== null ? $this->num($row->avg_buy_price, 8) : null,
            $row->price !== null ? $this->num($row->price, 8) : null,
            $row->open_tiers,
            $row->filled_tiers,
            $row->canceled_tiers,
            $hasOpen ? $this->num($row->open_amount, 8) : null,
            $hasOpen ? $this->num($row->open_cost, 2) : null,
            $hasOpen && $row->open_value !== null ? $this->num($row->open_value, 2) : null,
            $hasOpen && $row->unrealized_pnl !== null ? $this->num($row->unrealized_pnl, 2) : null,
            $this->num($row->realized_pnl, 2),
            $this->num($row->performance_fee, 2),
            $row->in_flight,
            $status,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);

        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }

    private function num(float|int|string|null $value, int $decimals): ?float
    {
        if ($value === null) {
            return null;
        }

        return round((float) $value, $decimals);
    }
}
