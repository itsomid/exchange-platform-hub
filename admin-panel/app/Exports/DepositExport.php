<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DepositExport implements FromCollection, WithHeadings
{
    protected $deposit;

    public function __construct(Collection $deposit)
    {
        $this->deposit = $deposit;
    }

    public function collection()
    {
        return $this->deposit;
    }

    public function headings(): array
    {

        return [
            'ID',
            'کاربر',
            'رمز ارز',
            'شبکه',
            'مقدار',
            'ارزش',
            'آدرس',
            'هش تراکنش',
            'تاریخ ایجاد',
            'تاریخ تایید',
            'وضعیت',
            'توضیحات واریز',
        ];
    }
}
