<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WithdrawalExport implements FromCollection, WithHeadings
{
    protected $withdrawal;

    public function __construct(Collection $withdrawal)
    {
        $this->withdrawal = $withdrawal;
    }

    public function collection()
    {
        return $this->withdrawal;
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
            'مقدار دریافتی کاربر',
            'کارمزد صرافی',
            'کارمزد برداشت',
            'آدرس',
            'هش تراکنش',
            'تاریخ درخواست',
            'تاریخ تکمیل برداشت',
            'وضعیت',
            'توضیحات واریز',
        ];
    }
}
