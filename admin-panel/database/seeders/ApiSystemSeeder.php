<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ApiSystem\System;
use App\Models\ApiSystem\SystemToken;
use Illuminate\Support\Str;

class ApiSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test API system
        $system = System::create([
            'name' => 'Test Trading System',
            'description' => 'سیستم تست برای تجارت و معاملات',
            'contact_email' => 'test@example.com',
            'allowed_ips' => ['127.0.0.1', '192.168.1.0/24'],
            'is_active' => true,
        ]);

        // Create tokens for the test system
        SystemToken::create([
            'system_id' => $system->id,
            'name' => 'Production Token',
            'token' => hash('sha256', 'test-production-token-' . time()),
            'scopes' => json_encode(['user_balance', 'stock_purchase']),
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        SystemToken::create([
            'system_id' => $system->id,
            'name' => 'Development Token',
            'token' => hash('sha256', 'test-development-token-' . time()),
            'scopes' => json_encode(['user_balance']),
            'expires_at' => now()->addMonths(6),
            'is_active' => true,
        ]);

        // Create another API system for demo
        $demoSystem = System::create([
            'name' => 'Demo Financial API',
            'description' => 'سیستم نمایشی برای خدمات مالی',
            'contact_email' => 'demo@financial.com',
            'allowed_ips' => json_encode(['*']), // Allow all IPs for demo
            'is_active' => true,
        ]);

        SystemToken::create([
            'system_id' => $demoSystem->id,
            'name' => 'Demo Token',
            'token' => hash('sha256', 'demo-token-' . time()),
            'scopes' => json_encode(['user_balance']),
            'expires_at' => now()->addMonths(3),
            'is_active' => true,
        ]);

        // Create an inactive system for testing
        $inactiveSystem = System::create([
            'name' => 'Inactive Test System',
            'description' => 'سیستم غیرفعال برای تست',
            'contact_email' => 'inactive@test.com',
            'allowed_ips' => json_encode(['192.168.1.100']),
            'is_active' => false,
        ]);

        SystemToken::create([
            'system_id' => $inactiveSystem->id,
            'name' => 'Inactive Token',
            'token' => hash('sha256', 'inactive-token-' . time()),
            'scopes' => json_encode([]),
            'expires_at' => now()->subDays(30),
            'is_active' => false,
        ]);

        $this->command->info('API Systems and Tokens seeded successfully!');
        $this->command->info('Test System ID: ' . $system->id);
        $this->command->info('Demo System ID: ' . $demoSystem->id);
        $this->command->info('Inactive System ID: ' . $inactiveSystem->id);
    }
}
