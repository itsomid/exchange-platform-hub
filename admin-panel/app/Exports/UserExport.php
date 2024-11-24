<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserExport implements FromCollection, WithHeadings
{
    protected $users;

    public function __construct(Collection $users)
    {
        $this->users = $users;
    }

    public function collection()
    {
        return $this->users;
    }

    public function headings(): array
    {

        return [
            'ID',
            'موبایل',
            'جنسیت',
            'نام فارسی',
            'نام انگلیسی',
            'توضیحات پشتیبان',
            'تاریخ عضویت',
        ];
    }
}
