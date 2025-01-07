<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\SavedAddressService\SaveAddressService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SaveAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $saveAddressService = new SaveAddressService();

        $users = User::where('id', '>', 1)->take(5)->get();
        foreach ($users as $user) {
            $name = fake()->userName;
            $address = 'txhash_' . bin2hex(random_bytes(10));
            $saveAddressService->saveAddress($user->id, 'BTC',$name, $address);
        }
    }
}
