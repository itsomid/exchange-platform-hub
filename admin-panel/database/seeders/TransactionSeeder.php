<?php

namespace Database\Seeders;

use App\Models\ReferralCode;
use App\Models\Transaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Transaction::factory(5)->withReferralCodeUsage()->create();
    }


}
