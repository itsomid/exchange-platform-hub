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
            'ایمیل',
            'نام کاربری',
            'نام',
            'کد معرف',
            'فرد معرفی کننده',
            'وضعیت کاربری',
            'آخرین فعالیت',
            'وضعیت دومرحله‌ای',
            'تاریخ تایید',
            'تاریخ ثبت نام'
        ];
    }
}
