<?php

namespace Database\Seeders;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Market;
use App\Models\User;
use App\Services\OTC\DTO\OTCRequestDTO;
use App\Services\OTC\OTCService;

use Illuminate\Database\Seeder;

class OTCBuySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $otcService = app(OTCService::class);
        $buyerUserId = User::find(4)->id;
        $sellerUserId = User::find(3)->id;
        $bitexroomUserId = config('bitexroom.user_id', 1);
        $firstMarket = Market::whereBaseCurrency('ETH')->first();
        $secondMarket = Market::whereBaseCurrency('BNB')->first();
        // Create a OTCRequestDTO with sample data
        $buyRequestDTO = (new OTCRequestDTO())
            ->setBuyerUserId($buyerUserId) // Example buyer user ID
            ->setSellerUserId($bitexroomUserId) // Example seller user ID
            ->setMarketId($firstMarket->id) // Example market ID
            ->setQuantity('0.1');


        $sellRequestDTO = (new OTCRequestDTO())
            ->setBuyerUserId($bitexroomUserId) // Example buyer user ID
            ->setSellerUserId($sellerUserId) // Example seller user ID
            ->setMarketId($secondMarket->id) // Example market ID
            ->setQuantity('1');
        // Execute the buy process
        try {
            // Execute the buy process
            $result = $otcService->buy($buyRequestDTO);
            $sellResult = $otcService->sell($sellRequestDTO);

            if ($result) {
                echo "OTC Buy transaction successfully seeded.\n";
            }
            if ($sellResult) {
                echo "OTC Sell transaction successfully seeded.\n";
            }
        } catch (InsufficientBalanceException $e) {
            echo "Transaction failed: " . $e->getMessage() . "\n";
        } catch (\Exception $e) {
            // Capture and display any other exceptions
            echo "Failed to seed OTC Buy transaction. Error: " . $e->getMessage() . "\n";
        }
    }
}
