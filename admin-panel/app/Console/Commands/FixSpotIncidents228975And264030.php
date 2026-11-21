<?php

namespace App\Console\Commands;

use App\Enums\TransactionTypeEnum;
use App\Models\Admin;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Services\Transaction\TransactionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Settles two earlier trades caused by the same spot matching bug as #368131:
 * a resting limit order of user #53 (nilaj0633) filled against another user's
 * in-flight market order, at the limit order's off-market price.
 *
 *   #228975 (2026-06-30, BNB)  — alptay79 (#432) bought 0.1717 BNB at 1700 instead
 *       of 547.914 (the price of the rest of that market order). User #53 pays the
 *       197.82 USDT difference in KNC at the live price; the exchange refunds
 *       alptay79 in USDT. KNC sell order #9341209 is cancelled to free the KNC.
 *   #264030 (2026-07-04, DOGE) — user #53 bought 100 DOGE for 5 USDT from
 *       rz.kenari (#85), whose wallet was never debited. The 100 DOGE go back to
 *       the exchange and user #53 gets the 5 USDT back (rz.kenari keeps the 5).
 *
 * Also cancels AUDIO sell order #9341210, whose coins were already taken in the
 * #368131 clawback. Run spot:fix-incident-368131 first: it returns the DOGE used here.
 *
 * Balance moves go through TransactionService::transferBetweenWallets, exactly
 * like an admin's manual transfer (withdrawal + deposit rows, two transactions).
 * Without --execute everything runs inside a transaction that is rolled back.
 */
class FixSpotIncidents228975And264030 extends Command
{
    protected $signature = 'spot:fix-incidents-228975-264030
        {--admin= : Id of the admin recorded on the transfers}
        {--execute : Commit the changes (default: dry-run that is rolled back)}';

    protected $description = 'Settle spot trades #228975 and #264030 (same matching bug as #368131)';

    private const SELLER    = 53;   // nilaj0633
    private const ALPTAY    = 432;  // alptay79
    private const EXCHANGE  = 1;
    private const BNB_OWED  = '197.82'; // 0.1717 × (1700 − 547.914), rounded up to USDT precision

    /** @var list<array{0:string,1:string}> */
    private array $summary = [];

    public function handle(TransactionService $transactions): int
    {
        $admin = Admin::find((int) $this->option('admin'));
        if ($admin === null) {
            $this->error('Pass --admin=<id> of an existing admin.');

            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        $this->line($execute
            ? '<fg=red;options=bold>Mode: EXECUTE — changes will be committed.</>'
            : '<fg=yellow;options=bold>Mode: DRY-RUN — everything is rolled back. Add --execute to commit.</>');
        $this->newLine();

        DB::beginTransaction();

        try {
            if ((int) DB::table('transactions')->where('id', 210399)->value('user_id') !== self::SELLER) {
                throw new \RuntimeException('run spot:fix-incident-368131 --execute first (it returns the 217.7 DOGE used here)');
            }

            $wallets = [[self::SELLER, 'KNC'], [self::SELLER, 'DOGE'], [self::SELLER, 'USDT'], [self::ALPTAY, 'USDT'],
                [self::EXCHANGE, 'KNC'], [self::EXCHANGE, 'DOGE'], [self::EXCHANGE, 'USDT']];
            $before = $this->balances($wallets);

            $this->cancelOrder(9341210, alsoClose: [4931]);
            $this->settleTrade264030($transactions, $admin);
            $this->settleTrade228975($transactions, $admin);

            $after = $this->balances($wallets);
            $this->table(['Step', 'Result'], $this->summary);
            $this->table(['Wallet', 'Before', 'After'], array_map(
                fn ($key) => [$key, $before[$key], $after[$key]],
                array_keys($before)
            ));

            if ($execute) {
                DB::commit();
                Log::warning('spot.fix_incidents_228975_264030', ['admin' => $admin->id, 'summary' => $this->summary]);
                $this->info('Committed.');
            } else {
                DB::rollBack();
                $this->warn('Rolled back (dry-run).');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage().' — nothing was changed.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function settleTrade264030(TransactionService $transactions, Admin $admin): void
    {
        if ($this->alreadySettled('#264030')) {
            $this->summary[] = ['trade #264030', 'already settled'];

            return;
        }

        $this->transfer($transactions, $admin, self::SELLER, self::EXCHANGE, '100', 'DOGE', 8, TransactionTypeEnum::WITHDRAWAL,
            'کسر بابت باگ تطبیق سفارش اسپات، معامله #264030 (2026-07-04): سفارش خرید لیمیت DOGE شما (#9341498) با قیمت 0.05 '
            .'با سفارش مارکت فروش در حال اجرای کاربر rz.kenari (#85) تطبیق خورد؛ به دلیل همین باگ این 100 DOGE از کیف پول فروشنده کسر نشد '
            .'و از هیچ ایجاد شده بود. 100 DOGE برگشت داده شد و 5 USDT پرداختی شما عودت شد.');

        $this->transfer($transactions, $admin, self::EXCHANGE, self::SELLER, '5', 'USDT', 3, TransactionTypeEnum::DEPOSIT,
            'عودت 5 USDT پرداختی بابت معامله اشتباه اسپات #264030 (2026-07-04) با کاربر rz.kenari (#85) به دلیل باگ تطبیق سفارش؛ '
            .'100 DOGE همان معامله از حساب شما کسر شد.');

        $this->note(264030, 'معامله اشتباه به دلیل باگ تطبیق سفارش (لیمیت با مارکت در حال اجرا). 100 DOGE از کاربر #53 برگشت و 5 USDT به او عودت شد؛ 5 USDT نزد rz.kenari ماند.');
        $this->summary[] = ['trade #264030', '100 DOGE user #53 → exchange; 5 USDT exchange → user #53'];
    }

    private function settleTrade228975(TransactionService $transactions, Admin $admin): void
    {
        if ($this->alreadySettled('#228975')) {
            $this->summary[] = ['trade #228975', 'already settled'];

            return;
        }

        $this->cancelOrder(9341209);

        $price = Currency::where('symbol', 'KNC')->first()?->baseMarket?->activeExchangePrice?->price;
        if ($price === null || bccomp((string) $price, '0', 8) <= 0) {
            throw new \RuntimeException('no live KNC price');
        }
        $knc = $this->divUp(self::BNB_OWED, (string) $price);

        $this->transfer($transactions, $admin, self::SELLER, self::EXCHANGE, $knc, 'KNC', 9, TransactionTypeEnum::WITHDRAWAL,
            'کسر بابت باگ تطبیق سفارش اسپات، معامله #228975 (2026-06-30): سفارش فروش لیمیت BNB شما (#9341208) با قیمت 1700 '
            .'با سفارش مارکت خرید در حال اجرای کاربر alptay79 (#432) تطبیق خورد، در حالی که قیمت بازار در همان لحظه 547.914 بود. '
            .'مابه‌التفاوت 0.1717 BNB برابر '.self::BNB_OWED." USDT معادل {$knc} KNC با قیمت {$price} کسر شد.");

        $this->transfer($transactions, $admin, self::EXCHANGE, self::ALPTAY, self::BNB_OWED, 'USDT', 3, TransactionTypeEnum::DEPOSIT,
            'عودت بابت باگ تطبیق سفارش اسپات، معامله #228975 (2026-06-30): سفارش مارکت خرید BNB شما (#9341458) با سفارش فروش لیمیت '
            .'کاربر nilaj0633 (#53) با قیمت 1700 تطبیق خورد، در حالی که قیمت بازار در همان لحظه 547.914 بود. '
            .'مابه‌التفاوت 0.1717 BNB برابر '.self::BNB_OWED.' USDT به حساب شما برگشت داده شد.');

        $this->note(228975, 'معامله اشتباه به دلیل باگ تطبیق سفارش (لیمیت با مارکت در حال اجرا). '.self::BNB_OWED." USDT به alptay79 عودت و معادل آن {$knc} KNC از کاربر #53 کسر شد.");
        $this->summary[] = ['trade #228975', "{$knc} KNC user #53 → exchange (KNC {$price}); ".self::BNB_OWED.' USDT exchange → alptay79'];
    }

    /** Same as the admin panel's order cancel (SpotOrderController::cancelOrder). */
    private function cancelOrder(int $orderId, array $alsoClose = []): void
    {
        $order = DB::table('spot_orders')->where('id', $orderId)->lockForUpdate()->first()
            ?? throw new \RuntimeException("spot order #{$orderId} not found");
        if ($order->status !== 'open') {
            $this->summary[] = ["order #{$orderId}", "already {$order->status}"];

            return;
        }
        if ((int) $order->user_id !== self::SELLER || bccomp($order->filled_quantity, '0', 8) !== 0) {
            throw new \RuntimeException("spot order #{$orderId} is not an unfilled order of user #".self::SELLER);
        }

        DB::table('spot_orders')->where('id', $orderId)->update(['status' => 'canceled', 'updated_at' => now()]);

        $description = 'آزاد کردن موجودی قفل شده به دلیل لغو سفارش توسط ادمین (باگ تطبیق سفارش اسپات)';
        $locks = DB::table('locked_balance_details')->whereNull('deleted_at')
            ->where(fn ($q) => $q->where('spot_order_id', $orderId)->orWhereIn('id', $alsoClose))
            ->lockForUpdate()->get();
        // Net per wallet: an admin release row ($alsoClose) offsets the order's own lock.
        foreach ($locks->groupBy('wallet_id') as $walletId => $walletLocks) {
            $release = $walletLocks->reduce(fn ($sum, $lock) => bcadd($sum, $lock->amount, 8), '0');
            $wallet = DB::table('wallets')->where('id', $walletId)->lockForUpdate()->first();
            $locked = bcsub($wallet->locked_balance, $release, 8);
            DB::table('wallets')->where('id', $walletId)->update([
                'locked_balance' => bccomp($locked, '0', 8) < 0 ? '0' : $locked,
                'updated_at' => now(),
            ]);
        }
        DB::table('locked_balance_details')->whereIn('id', $locks->pluck('id'))
            ->update(['description' => $description, 'deleted_at' => now(), 'updated_at' => now()]);

        $this->summary[] = ["order #{$orderId}", 'canceled, '.$locks->count().' lock row(s) released'];
    }

    private function transfer(TransactionService $transactions, Admin $admin, int $from, int $to, string $amount, string $symbol,
        int $chainId, TransactionTypeEnum $type, string $adminDescription): void
    {
        $currency = Currency::where('symbol', $symbol)->firstOrFail();
        $chain = CurrencyChain::findOrFail($chainId);
        if ($chain->currency_id !== $currency->id) {
            throw new \RuntimeException("chain #{$chainId} is not a {$symbol} chain");
        }

        $transactions->transferBetweenWallets(
            fromUserId: $from,
            toUserId: $to,
            amount: (float) $amount,
            transactionHash: null,
            currency: $currency,
            currencyChain: $chain,
            type: $type->value,
            adminId: $admin->id,
            description: 'Manual transfer by admin #'.$admin->id,
            admin_description: $adminDescription,
        );
    }

    private function alreadySettled(string $trade): bool
    {
        return DB::table('transactions')->where('subtype', 'manual_admin')
            ->where('admin_description', 'like', "%{$trade}%")->exists();
    }

    private function note(int $tradeId, string $note): void
    {
        DB::table('spot_trades')->where('id', $tradeId)->whereNull('notes')->update(['notes' => $note, 'updated_at' => now()]);
    }

    private function divUp(string $amount, string $price): string
    {
        $q = bcdiv($amount, $price, 8);

        return bccomp(bcmul($q, $price, 16), $amount, 16) < 0 ? bcadd($q, '0.00000001', 8) : $q;
    }

    /** @return array<string,string> */
    private function balances(array $wallets): array
    {
        $out = [];
        foreach ($wallets as [$userId, $symbol]) {
            $out["user {$userId} {$symbol}"] = (string) DB::table('wallets')
                ->where('user_id', $userId)->where('currency_symbol', $symbol)->value('balance');
        }

        return $out;
    }
}
