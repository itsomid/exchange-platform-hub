<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class NormalizePersonalAccessTokenIp extends Command
{
    protected $signature = 'tokens:normalize-ip {--dry-run} {--force} {--chunk=500}';
    protected $description = 'Keep only the first IP in personal access tokens ip column';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $chunkSize = (int) $this->option('chunk');

        $query = PersonalAccessToken::query()
            ->whereNotNull('ip')
            ->where('ip', 'like', '%,%');

        $count = $query->count();

        if ($count === 0) {
            $this->info('No tokens with multiple IPs found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} tokens to normalize.");

        if ($dryRun) {
            $preview = $query->limit(20)->get()->map(function ($t) {
                $newIp = trim(explode(',', $t->ip)[0]);
                return [
                    $t->id,
                    $t->ip,
                    $newIp,
                ];
            })->toArray();

            $this->table(['ID', 'Old IP', 'New IP'], $preview);
            $this->warn('Dry-run mode. No changes applied.');
            return Command::SUCCESS;
        }

        if (!$force && !$this->confirm("Update {$count} tokens?")) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $updated = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->orderBy('id')->chunkById($chunkSize, function ($tokens) use (&$updated, $bar) {
            foreach ($tokens as $token) {
                $newIp = trim(explode(',', $token->ip)[0]);
                if ($newIp !== $token->ip) {
                    $token->ip = $newIp;
                    try {
                        $token->save();
                        $updated++;
                    } catch (\Throwable $e) {
                        $this->error("Failed to update token ID {$token->id}: " . $e->getMessage());
                    }
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Updated {$updated} tokens.");
        return Command::SUCCESS;
    }
}

