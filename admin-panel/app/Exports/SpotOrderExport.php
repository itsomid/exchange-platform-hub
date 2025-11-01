<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SpotOrderExport implements FromCollection, WithHeadings
{
    protected $spotOrders;

    public function __construct(Collection $spotOrders)
    {
        $this->spotOrders = $spotOrders;
    }

    public function collection()
    {
        return $this->spotOrders;
    }

    public function headings(): array
    {
        return [
            'ID',
            'کاربر',
            'ایمیل کاربر',
            'بازار',
            'سمت',
            'نوع',
            'مقدار',
            'قیمت',
            'مقدار پر شده',
            'وضعیت',
            'منبع',
            'تاریخ ایجاد',
        ];
    }
}