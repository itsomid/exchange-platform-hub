<?php

namespace App\Console\Commands;

use App\Enums\DepositStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FixTooSmallDepositTransactions extends Command
{
    protected $signature = 'deposits:fix-too-small-transactions
                            {--execute : Apply the update. Without this flag the command only previews matching rows}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Copy coin_price from premature too_small txs onto the admin-approval txs, then delete the premature txs and set leftover approval txs to user_initiated.';

    public function handle(): int
    {
        $premature = $this->prematureTransactionQuery()
            ->with('deposit:id,status,description')
            ->orderBy('id')
            ->get(['id', 'deposit_id', 'user_id', 'amount', 'coin_price', 'subtype', 'description', 'created_at']);

        $approval = $this->approvalTransactionQuery()
            ->with('deposit:id,status,description')
            ->orderBy('id')
            ->get(['id', 'deposit_id', 'user_id', 'amount', 'coin_price', 'subtype', 'description', 'admin_description', 'created_at']);

        $priceCopies = $this->priceCopyPairs($premature, $approval);

        $this->info('Premature too_small transactions to delete: '.$premature->count());
        $this->comment('These were created before admin approval (description starts with "واریز به آدرس").');
        $this->newLine();

        if ($premature->isNotEmpty()) {
            $this->table(
                ['id', 'deposit_id', 'deposit_status', 'coin_price', 'subtype', 'created_at', 'description'],
                $premature->take(20)->map(fn (Transaction $tx) => [
                    $tx->id,
                    $tx->deposit_id,
                    $tx->deposit?->status?->value,
                    $tx->coin_price,
                    $tx->subtype?->value,
                    $tx->created_at,
                    Str::limit((string) $tx->description, 70),
                ])->all()
            );

            if ($premature->count() > 20) {
                $this->comment('Showing first 20 of '.$premature->count().' premature transactions.');
            }
            $this->newLine();
        }

        $this->info('coin_price copies from first tx → approval tx: '.$priceCopies->count());
        $this->comment('The approval tx had no coin_price; it will be taken from the premature too_small tx before that row is deleted.');
        $this->newLine();

        if ($priceCopies->isNotEmpty()) {
            $this->table(
                ['deposit_id', 'from_tx_id', 'from_coin_price', 'to_tx_id', 'to_coin_price'],
                $priceCopies->take(20)->map(fn (array $pair) => [
                    $pair['deposit_id'],
                    $pair['from']->id,
                    $pair['from']->coin_price,
                    $pair['to']->id,
                    $pair['to']->coin_price,
                ])->all()
            );

            if ($priceCopies->count() > 20) {
                $this->comment('Showing first 20 of '.$priceCopies->count().' coin_price copies.');
            }
            $this->newLine();
        }

        $this->info('Admin-approval transactions to set subtype=user_initiated: '.$approval->count());
        $this->comment('Matched by "واریز تایید شده توسط ادمین" / "تایید واریزی کمتر از حد مجاز".');
        $this->newLine();

        if ($approval->isNotEmpty()) {
            $this->table(
                ['id', 'deposit_id', 'subtype', 'coin_price', 'created_at', 'description'],
                $approval->take(20)->map(fn (Transaction $tx) => [
                    $tx->id,
                    $tx->deposit_id,
                    $tx->subtype?->value,
                    $tx->coin_price,
                    $tx->created_at,
                    Str::limit((string) $tx->description, 70),
                ])->all()
            );

            if ($approval->count() > 20) {
                $this->comment('Showing first 20 of '.$approval->count().' approval transactions.');
            }
            $this->newLine();
        }

        if ($premature->isEmpty() && $approval->isEmpty()) {
            $this->info('Nothing to do.');

            return self::SUCCESS;
        }

        if (! $this->option('execute')) {
            $this->warn('Dry-run only. No rows were changed.');
            $this->warn('To apply: php artisan deposits:fix-too-small-transactions --execute');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Copy coin_price onto {$priceCopies->count()} approval tx(s), update subtype on {$approval->count()} approval tx(s), and delete {$premature->count()} premature tx(s)?")) {
            $this->info('Cancelled. No rows were changed.');

            return self::SUCCESS;
        }

        $result = DB::transaction(function () use ($premature, $approval, $priceCopies) {
            $priceCopied = 0;
            foreach ($priceCopies as $pair) {
                $priceCopied += Transaction::query()
                    ->where('id', $pair['to']->id)
                    ->update(['coin_price' => $pair['from']->coin_price]);
            }

            $updated = 0;
            if ($approval->isNotEmpty()) {
                $updated = $this->approvalTransactionQuery()
                    ->whereIn('id', $approval->pluck('id'))
                    ->update(['subtype' => TransactionSubTypeEnum::USER_INITIATED->value]);
            }

            $deleted = 0;
            if ($premature->isNotEmpty()) {
                $deleted = $this->prematureTransactionQuery()
                    ->whereIn('id', $premature->pluck('id'))
                    ->delete();
            }

            return [
                'price_copied' => $priceCopied,
                'updated' => $updated,
                'deleted' => $deleted,
            ];
        });

        Log::info('deposits:fix-too-small-transactions', [
            'price_copy_pairs' => $priceCopies->map(fn (array $pair) => [
                'deposit_id' => $pair['deposit_id'],
                'from_id' => $pair['from']->id,
                'to_id' => $pair['to']->id,
                'coin_price' => $pair['from']->coin_price,
            ])->all(),
            'deleted_ids' => $premature->pluck('id')->all(),
            'updated_ids' => $approval->pluck('id')->all(),
            'price_copied' => $result['price_copied'],
            'updated' => $result['updated'],
            'deleted' => $result['deleted'],
        ]);

        $this->info("Copied coin_price onto {$result['price_copied']} approval transaction(s).");
        $this->info("Updated {$result['updated']} approval transaction(s) to user_initiated.");
        $this->info("Deleted {$result['deleted']} premature transaction(s).");

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Transaction>  $premature
     * @param  \Illuminate\Support\Collection<int, Transaction>  $approval
     * @return \Illuminate\Support\Collection<int, array{deposit_id: int, from: Transaction, to: Transaction}>
     */
    private function priceCopyPairs($premature, $approval)
    {
        $prematureByDeposit = $premature->groupBy('deposit_id');

        return $approval
            ->map(function (Transaction $approvalTx) use ($prematureByDeposit) {
                $source = $prematureByDeposit->get($approvalTx->deposit_id)?->sortBy('id')->first();

                if (! $source || $source->coin_price === null) {
                    return null;
                }

                return [
                    'deposit_id' => $approvalTx->deposit_id,
                    'from' => $source,
                    'to' => $approvalTx,
                ];
            })
            ->filter()
            ->values();
    }

    private function prematureTransactionQuery()
    {
        return Transaction::query()
            ->whereNotNull('deposit_id')
            ->where('type', TransactionTypeEnum::DEPOSIT->value)
            ->where('description', 'like', 'واریز به آدرس:%')
            ->where(function ($query) {
                $query->whereHas('deposit', function ($depositQuery) {
                    $depositQuery->where('status', DepositStatusEnum::TOO_SMALL->value)
                        ->orWhere('description', 'like', '%به علت پایین بودن از حد مجاز%');
                })->orWhereHas('deposit.transactions', function ($transactionQuery) {
                    $transactionQuery->where(function ($inner) {
                        $inner->where('description', 'like', '%واریز تایید شده توسط ادمین%')
                            ->orWhere('admin_description', 'like', '%تایید واریزی کمتر از حد مجاز%');
                    });
                });
            });
    }

    private function approvalTransactionQuery()
    {
        return Transaction::query()
            ->whereNotNull('deposit_id')
            ->where('type', TransactionTypeEnum::DEPOSIT->value)
            ->where('subtype', TransactionSubTypeEnum::MANUAL_ADMIN->value)
            ->where(function ($query) {
                $query->where('description', 'like', '%واریز تایید شده توسط ادمین%')
                    ->orWhere('admin_description', 'like', '%تایید واریزی کمتر از حد مجاز%');
            });
    }
}
