<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\User;
use App\Services\Mail\MailFailureLogger;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;

class AuditMailRecipients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:audit-recipients
                            {--skip-mx : Skip DNS lookups for recipient domains}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find users and admins that cannot receive email and log the reason';

    /**
     * Execute the console command.
     */
    public function handle(MailFailureLogger $logger): int
    {
        $stats = ['checked' => 0, 'flagged' => 0, 'reasons' => [], 'dns_failed' => 0];
        $dnsCache = [];
        $checkDns = ! $this->option('skip-mx');

        $this->audit(User::withTrashed(), $logger, $checkDns, $dnsCache, $stats);
        $this->audit(Admin::query(), $logger, $checkDns, $dnsCache, $stats);

        $this->newLine();
        $this->info("Checked {$stats['checked']} accounts; {$stats['flagged']} cannot receive email.");

        foreach ($stats['reasons'] as $reason => $count) {
            $this->line("  {$reason}: {$count}");
        }

        if ($stats['dns_failed'] > 0) {
            $this->warn("DNS lookup failed for {$stats['dns_failed']} domain(s); those accounts were not flagged.");
        }

        if ($stats['flagged'] > 0) {
            $this->warn('Details were written to '.storage_path('logs/mail-failures.log'));
        }

        return self::SUCCESS;
    }

    /**
     * @param array<string, ?bool>                                                            $dnsCache
     * @param array{checked: int, flagged: int, reasons: array<string, int>, dns_failed: int} $stats
     */
    private function audit(
        Builder $query,
        MailFailureLogger $logger,
        bool $checkDns,
        array &$dnsCache,
        array &$stats,
    ): void {
        $query->chunkById(500, function ($accounts) use ($logger, $checkDns, &$dnsCache, &$stats) {
            foreach ($accounts as $account) {
                $stats['checked']++;

                [$reason, $detail] = $this->detect($account->email, $checkDns, $dnsCache, $stats);

                if ($reason === null) {
                    continue;
                }

                $stats['flagged']++;
                $stats['reasons'][$reason] = ($stats['reasons'][$reason] ?? 0) + 1;

                $logger->accountCannotReceiveMail($account, $reason, $detail);

                $this->line(sprintf(
                    '%s #%d %s — %s',
                    $account instanceof Admin ? 'admin' : 'user',
                    $account->id,
                    $account->email ?: '(empty)',
                    $detail,
                ));
            }
        });
    }

    /**
     * @param  array<string, ?bool>                                                            $dnsCache
     * @param  array{checked: int, flagged: int, reasons: array<string, int>, dns_failed: int} $stats
     * @return array{0: ?string, 1: ?string}
     */
    private function detect(?string $email, bool $checkDns, array &$dnsCache, array &$stats): array
    {
        $email = trim((string) $email);

        if ($email === '') {
            return ['missing_email', 'Account has no email address.'];
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['invalid_format', 'Email address is not valid (FILTER_VALIDATE_EMAIL).'];
        }

        if (! $checkDns) {
            return [null, null];
        }

        $domain = strtolower(substr(strrchr($email, '@') ?: '@', 1));

        if ($domain === '') {
            return ['invalid_format', 'Email address has no domain part.'];
        }

        if (! array_key_exists($domain, $dnsCache)) {
            $dnsCache[$domain] = $this->domainExists($domain);

            if ($dnsCache[$domain] === null) {
                $stats['dns_failed']++;
            }
        }

        if ($dnsCache[$domain] === false) {
            return ['domain_not_found', "Domain {$domain} does not exist (DNS NXDOMAIN)."];
        }

        return [null, null];
    }

    /**
     * Resolve the domain over DNS-over-HTTPS so the audit cannot hang on the
     * system resolver's multi-second retries for non-existent domains.
     *
     * @return bool|null True when the domain exists, false on NXDOMAIN and
     *                   null when the lookup itself failed.
     */
    private function domainExists(string $domain): ?bool
    {
        try {
            $response = Http::withHeaders(['Accept' => 'application/dns-json'])
                ->timeout(5)
                ->get('https://cloudflare-dns.com/dns-query', [
                    'name' => $domain,
                    'type' => 'MX',
                ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return match ($response->json('Status')) {
            0 => true,
            3 => false,
            default => null,
        };
    }
}
