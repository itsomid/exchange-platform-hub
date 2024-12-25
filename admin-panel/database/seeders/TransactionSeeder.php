<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\OTC\DTO\BuyRequestDTO;
use App\Services\OTC\OTCService;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Transaction::factory(5)->withReferralCodeUsage()->create();

        for ($i = 0; $i <= 10; $i++) {
            $market = Market::query()->inRandomOrder()->first();
            $user = User::factory()
                ->has(
                    Wallet::factory(2)
                        ->sequence(
                            ['balance' => 0, 'currency_symbol' => $market->base_currency],
                            ['balance' => 1000000, 'currency_symbol' => 'USDT']
                        )
                )
                ->create();

            resolve(OTCService::class)
                ->buy(
                    resolve(BuyRequestDTO::class)
                        ->setSellerUserId(1)
                        ->setBuyerUserId($user->id)
                        ->setMarketId($market->id)
                        ->setQuantity(fake()->randomFloat(0.001, 1.5))
                );
        }

    }
}
