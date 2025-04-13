<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OTCOrderExport implements FromCollection, WithHeadings
{
    protected $otc_orders;

    public function __construct(Collection $otc_orders)
    {
        $this->otc_orders = $otc_orders;
    }

    public function collection()
    {
        return $this->otc_orders;
    }

    public function headings(): array
    {

        return [
            'ID',
            'بازار',
            'نوع معامله',
            'کاربر',
            'مقدار',
            'قیمت واحد',
            'مبلغ کل',
            'کارمزد',
            'دریافتی',
            'تاریخ',
            'وضعیت',
            'صرافی مرجع',
            'توضیحات'
        ];
    }
}
