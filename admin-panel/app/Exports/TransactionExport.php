<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransactionExport implements FromCollection, WithHeadings
{
    protected $transactions;

    public function __construct(Collection $transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {

        return [
            'ID',
            'نوع تراکنش',
            'نوع تراکنش (subType)',
            'کاربر',
            'رمز ارز',
            'مقدار',
            'موجودی قبل تراکنش',
            'توضیحات',
            'تاریخ',
            'وضعیت',
            'ایجاد توسط ادمین',
            'توضیحات ادمین',
        ];
    }
}
