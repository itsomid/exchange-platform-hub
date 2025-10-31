<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SpotTradeExport implements FromCollection, WithHeadings
{
    protected $spotTrades;

    public function __construct(Collection $spotTrades)
    {
        $this->spotTrades = $spotTrades;
    }

    public function collection()
    {
        return $this->spotTrades;
    }

    public function headings(): array
    {
        return [
            'ID',
            'بازار',
            'کاربر Maker',
            'کاربر Taker',
            'مقدار',
            'قیمت',
            'ارزش معامله',
            'کارمزد Maker',
            'کارمزد Taker',
            'کل کارمزد',
            'نوع سفارش Maker',
            'نوع سفارش Taker',
            'وضعیت Maker',
            'وضعیت Taker',
            'تاریخ معامله',
        ];
    }
}