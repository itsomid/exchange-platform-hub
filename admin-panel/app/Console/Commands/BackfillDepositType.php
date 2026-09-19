<?php

namespace App\Console\Commands;

use App\Enums\DepositTypeEnum;
use App\Models\Deposit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BackfillDepositType extends Command
{
    protected $signature = 'deposits:backfill-type
                            {--execute : Apply the update. Without this flag the command only previews matching rows}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Backfill deposits.type: manual_admin when related tx description is "Manual transfer by admin" (or deposit description is a known admin credit), otherwise user_initiated.';

    /**
     * @return list<string>
     */
    private function adminDescriptionNeedles(): array
    {
        return [
            'manual transfer by admin',
            'manual deposit by admin',
            'manual credit increase by admin',
            'credit increase by admin',
        ];
    }

    public function handle(): int
    {
        $adminQuery = $this->adminDepositQuery();
        $adminCount = (clone $adminQuery)->count();
        $total = Deposit::query()->count();
        $userCount = max(0, $total - $adminCount);

        $this->info("Total deposits: {$total}");
        $this->info("Would set type=manual_admin: {$adminCount}");
        $this->info("Would set type=user_initiated: {$userCount}");
        $this->comment('manual_admin matches: deposit or related transaction description contains "manual transfer by admin" / "manual deposit by admin" / "credit increase by admin".');
        $this->newLine();

        if ($adminCount > 0) {
            $sample = (clone $adminQuery)
                ->with('transaction:id,deposit_id,description')
                ->orderBy('id')
                ->limit(20)
                ->get(['id', 'user_id', 'transaction_hash', 'description', 'type', 'created_at']);

            $this->table(
                ['id', 'user_id', 'has_txhash', 'type', 'created_at', 'description'],
                $sample->map(fn (Deposit $deposit) => [
                    $deposit->id,
                    $deposit->user_id,
                    $deposit->transaction_hash ? 'yes' : 'no',
                    $deposit->type?->value ?? $deposit->getRawOriginal('type'),
                    $deposit->created_at,
                    Str::limit((string) ($deposit->description ?: $deposit->transaction?->description), 80),
                ])->all()
            );

            if ($adminCount > 20) {
                $this->comment('Showing first 20 of '.$adminCount.' matching admin deposits.');
            }
        }

        if ($total === 0) {
            $this->info('No deposits. Nothing to do.');

            return self::SUCCESS;
        }

        if (! $this->option('execute')) {
            $this->warn('Dry-run only. No rows were changed.');
            $this->warn('To apply: php artisan deposits:backfill-type --execute');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Update type on {$total} deposit row(s)?")) {
            $this->info('Cancelled. No rows were updated.');

            return self::SUCCESS;
        }

        $updated = DB::transaction(function () {
            $userUpdated = Deposit::query()->update(['type' => DepositTypeEnum::USER_INITIATED->value]);
            $adminUpdated = $this->adminDepositQuery()->update(['type' => DepositTypeEnum::MANUAL_ADMIN->value]);

            return [
                'manual_admin' => $adminUpdated,
                'user_initiated' => max(0, $userUpdated - $adminUpdated),
            ];
        });

        Log::info('deposits:backfill-type updated deposits', $updated);

        $this->info("Updated {$updated['manual_admin']} row(s) to manual_admin.");
        $this->info("Updated {$updated['user_initiated']} row(s) to user_initiated.");

        return self::SUCCESS;
    }

    private function adminDepositQuery()
    {
        $needles = $this->adminDescriptionNeedles();

        return Deposit::query()->where(function ($query) use ($needles) {
            $query->where(function ($descriptionQuery) use ($needles) {
                foreach ($needles as $needle) {
                    $descriptionQuery->orWhere('description', 'like', '%'.$needle.'%');
                }
            })->orWhereHas('transactions', function ($transactionQuery) use ($needles) {
                $transactionQuery->where(function ($inner) use ($needles) {
                    foreach ($needles as $needle) {
                        $inner->orWhere('description', 'like', '%'.$needle.'%');
                    }
                });
            });
        });
    }
}
