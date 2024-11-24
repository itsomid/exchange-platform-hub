<?php

namespace App\Imports;

use App\Exceptions\InvalidExcelException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\withUpserts;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class UsersImport implements ToModel, WithBatchInserts, WithHeadingRow, withUpserts
{
    private array $createdUsers = [];

    private $saleSupportId;

    private $rowCount = 0;

    /**
     * @param  array  $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function __construct($saleSupportId)
    {
        $this->saleSupportId = $saleSupportId;
    }

    public function model(array $row)
    {
        //        HeadingRowFormatter::default('none');
        if (! isset($row['firstname'])) {
            throw new InvalidExcelException('فایل اکسل مشکل دارد. ستون firstname موجود نیست');
        }

        if (! isset($row['mobile'])) {
            throw new InvalidExcelException('فایل اکسل مشکل دارد. ستون mobile موجود نیست');
        }

        if (! preg_match('/(09)[0-9]{9}/', $row['mobile'])) {
            throw new InvalidExcelException('برخی شماره ها اشتباه هستند.(شماره باید ۱۰ رقم باشد و صفر در ابتدای شماره باشد).');
        }
        $existingUser = User::where('mobile', $row['mobile'])->first();
        if ($existingUser) {
            return null;
        } else {
            $this->rowCount++;
        }

        $user = new User([
            'name' => $row['firstname'].' '.$row['lastname'],
            'mobile' => $row['mobile'],
            'sales_description' => $row['description'],
            'password' => Hash::make(rand()),
            'created_at' => now(),
        ]);
        $user->save();
        $this->createdUsers[] = $user;

        return $user;
    }

    public function getCreatedUsers()
    {
        return $this->createdUsers;
    }

    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function uniqueBy()
    {
        return 'mobile';
    }
}
