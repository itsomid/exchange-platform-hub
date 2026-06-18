<?php

namespace App\Console\Commands;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Resets the demo state for the BotTradingScenariosSeeder:
 *   - wipes all bot tables for users 2..6
 *   - resets their bot_wallet to zero
 *   - sets their main USDT wallet to 500 (creating it if missing)
 *
 * After running this, `php artisan db:seed --class=BotTradingScenariosSeeder`
 * starts from a clean, predictable baseline.
 */
class BotScenariosReset extends Command
{
    protected $signature = 'bot:scenarios-reset
                            {--users=2,3,4,5,6 : Comma-separated user IDs to reset}
                            {--main-balance=500 : USDT balance to set on each main wallet}
                            {--reseed : Run BotTradingScenariosSeeder after the reset}';

    protected $description = 'Wipe bot data + reset main USDT wallet for the scenarios seeder users.';

    public function handle(): int
    {
        $userIds = collect(explode(',', (string) $this->option('users')))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->values()
            ->all();

        $mainBalance = (float) $this->option('main-balance');

        $existing = User::whereIn('id', $userIds)->pluck('id')->all();
        $missing  = array_diff($userIds, $existing);
        if (! empty($missing)) {
            $this->warn('Skipping non-existent users: '.implode(',', $missing));
        }
        if (empty($existing)) {
            $this->error('No matching users.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($existing, $mainBalance) {
            // 1) wipe bot tables (FK-safe order)
            $orderIds = BotOrder::whereIn('user_id', $existing)->pluck('id')->all();
            $execIds  = BotBuyExecution::whereIn('bot_order_id', $orderIds)->pluck('id')->all();

            BotTradeSettlement::whereIn('user_id', $existing)->delete();
            if (! empty($execIds)) {
                BotSellOrder::whereIn('bot_buy_execution_id', $execIds)->delete();
            }
            BotBuyExecution::whereIn('bot_order_id', $orderIds)->delete();
            BotOrder::whereIn('user_id', $existing)->delete();
            BotUserSettings::whereIn('user_id', $existing)->delete();
            BotWallet::whereIn('user_id', $existing)->delete();

            // 2) reset main USDT wallet (create if missing)
            foreach ($existing as $uid) {
                Wallet::updateOrCreate(
                    ['user_id' => $uid, 'currency_symbol' => 'USDT'],
                    ['balance' => $mainBalance, 'locked_balance' => 0],
                );
            }
        });

        $this->info('Reset complete for users: '.implode(',', $existing));
        $this->line("  • bot_* tables cleared");
        $this->line("  • main USDT wallet = {$mainBalance}");
        $this->line("  • bot_wallet removed (seeder will recreate)");

        if ($this->option('reseed')) {
            $this->line('');
            $this->info('Re-seeding scenarios...');
            $this->call('db:seed', ['--class' => \Database\Seeders\BotTradingScenariosSeeder::class]);
        }

        return self::SUCCESS;
    }
}
