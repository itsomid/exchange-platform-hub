<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Services\OTC\DTO\BuyRequestDTO;
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
        $market = Market::where('base_currency','BTC')->first();
        // Create a BuyRequestDTO with sample data
        $buyRequestDTO = (new BuyRequestDTO())
            ->setBuyerUserId(2) // Example buyer user ID
            ->setSellerUserId(1) // Example seller user ID
            ->setMarketId($market->id) // Example market ID
            ->setQuantity('0.1'); // Buying 0.1 Bitcoin

        // Execute the buy process
        try {
            // Execute the buy process
            $result = $otcService->buy($buyRequestDTO);

            if ($result) {
                echo "OTC Buy transaction successfully seeded.\n";
            } else {
                echo "Failed to seed OTC Buy transaction. No specific error provided.\n";
            }
        } catch (\Exception $e) {
            // Capture and display the reason for failure
            echo "Failed to seed OTC Buy transaction. Error: " . $e->getMessage() . "\n";
        }
    }
}
