<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HdWalletIndexBalanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $balances;
    protected string $currencySymbol;
    protected string $chainName;

    public function __construct(Collection $balances, string $currencySymbol, string $chainName)
    {
        $this->balances = $balances;
        $this->currencySymbol = $currencySymbol;
        $this->chainName = $chainName;
    }

    public function collection()
    {
        return $this->balances;
    }

    public function map($row): array
    {
        return [
            $row->hd_wallet_index,
            $row->currency_symbol,
            $this->chainName,
            $row->total_balance,
            $row->deposit_count,
            $row->first_deposit_at ? \Morilog\Jalali\Jalalian::forge($row->first_deposit_at)->format('Y/m/d H:i') : '-',
            $row->last_deposit_at ? \Morilog\Jalali\Jalalian::forge($row->last_deposit_at)->format('Y/m/d H:i') : '-',
        ];
    }

    public function headings(): array
    {
        return [
            'ایندکس HD Wallet (شناسه کاربر)',
            'رمز ارز',
            'شبکه',
            'موجودی کل',
            'تعداد واریز',
            'اولین واریز',
            'آخرین واریز',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
