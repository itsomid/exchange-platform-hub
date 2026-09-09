<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\SweeperTransactionLog;
use App\Services\Sweeper\CreateSweeperAccountingTransactionsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SweeperTransactionLogController extends Controller
{
    private const PAGE_LIMIT = 100;

    public function __construct(
        private readonly CreateSweeperAccountingTransactionsService $createAccountingTransactionsService
    ) {
    }

    public function index(Request $request): View
    {
        $logs = SweeperTransactionLog::query()
            ->orderByDesc('broadcast_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total' => SweeperTransactionLog::count(),
            'last_synced_at' => SweeperTransactionLog::query()->max('synced_at'),
            'pending_accounting' => SweeperTransactionLog::query()
                ->where(function ($query) {
                    $query->whereNull('withdrawal_transaction_id')
                        ->orWhereNull('fee_transaction_id');
                })
                ->count(),
            'accounted' => SweeperTransactionLog::query()
                ->whereNotNull('withdrawal_transaction_id')
                ->whereNotNull('fee_transaction_id')
                ->count(),
        ];

        return view('dashboard.hd-wallet.sweeper-transactions.index', compact('logs', 'stats'));
    }

    public function sync(): JsonResponse
    {
        $baseUrl = rtrim((string) config('sweeper.base_url'), '/');
        $apiKey = (string) config('sweeper.api_key');

        if ($baseUrl === '' || $apiKey === '') {
            return response()->json([
                'success' => false,
                'message' => 'تنظیمات sweeper ناقص است (SWEEPER_BASE_URL / SWEEPER_API_KEY).',
            ], 422);
        }

        try {
            $startedAt = now();
            $startedMicro = microtime(true);
            $fetched = 0;
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $page = 1;
            $totalPages = 1;
            $remoteTotalCount = 0;
            $byNetwork = [];
            $bySymbol = [];
            $byType = [];
            $createdSamples = [];
            $updatedSamples = [];

            do {
                $response = Http::timeout(60)
                    ->acceptJson()
                    ->withHeaders(['x-api-key' => $apiKey])
                    ->get("{$baseUrl}/api/logs/transactions", [
                        'status' => 'broadcasted',
                        'type' => 'sweep',
                        'page' => $page,
                        'limit' => self::PAGE_LIMIT,
                        'sortBy' => 'createdAt',
                        'sortOrder' => 'desc',
                    ]);

                if (!$response->successful()) {
                    Log::warning('Sweeper transaction sync failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'page' => $page,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'خطا در دریافت تراکنش‌ها از sweeper (HTTP ' . $response->status() . ').',
                    ], 502);
                }

                $payload = $response->json();
                if (!($payload['success'] ?? false)) {
                    return response()->json([
                        'success' => false,
                        'message' => $payload['error'] ?? 'پاسخ نامعتبر از sweeper دریافت شد.',
                    ], 502);
                }

                $transactions = $payload['data']['transactions'] ?? [];
                $pagination = $payload['data']['pagination'] ?? [];
                $totalPages = max(1, (int) ($pagination['totalPages'] ?? 1));
                $remoteTotalCount = (int) ($pagination['totalCount'] ?? $remoteTotalCount);

                foreach ($transactions as $tx) {
                    $result = $this->upsertTransaction($tx);
                    $fetched++;

                    $network = (string) ($tx['network'] ?? 'unknown');
                    $symbol = strtoupper((string) ($tx['symbol'] ?? 'unknown'));
                    $type = (string) ($tx['type'] ?? 'unknown');
                    $byNetwork[$network] = ($byNetwork[$network] ?? 0) + 1;
                    $bySymbol[$symbol] = ($bySymbol[$symbol] ?? 0) + 1;
                    $byType[$type] = ($byType[$type] ?? 0) + 1;

                    $sample = [
                        'sweeper_id' => (string) ($tx['_id'] ?? $tx['id'] ?? ''),
                        'tx_hash' => $tx['txHash'] ?? null,
                        'network' => $network,
                        'symbol' => $symbol,
                        'type' => $type,
                        'amount' => isset($tx['amount']) ? (string) $tx['amount'] : null,
                    ];

                    if ($result === 'created') {
                        $created++;
                        if (count($createdSamples) < 8) {
                            $createdSamples[] = $sample;
                        }
                    } elseif ($result === 'updated') {
                        $updated++;
                        if (count($updatedSamples) < 8) {
                            $updatedSamples[] = $sample;
                        }
                    } else {
                        $skipped++;
                    }
                }

                $page++;
            } while ($page <= $totalPages);

            arsort($byNetwork);
            arsort($bySymbol);
            arsort($byType);

            $finishedAt = now();
            $durationMs = (int) max(0, round((microtime(true) - $startedMicro) * 1000));

            return response()->json([
                'success' => true,
                'message' => "همگام‌سازی انجام شد. دریافت: {$fetched} | جدید: {$created} | به‌روزرسانی: {$updated}",
                'data' => [
                    'fetched' => $fetched,
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'pages_fetched' => $totalPages,
                    'page_limit' => self::PAGE_LIMIT,
                    'remote_total_count' => $remoteTotalCount,
                    'total' => SweeperTransactionLog::count(),
                    'duration_ms' => $durationMs,
                    'duration_human' => $this->formatDuration($durationMs),
                    'filter' => [
                        'status' => 'broadcasted',
                        'type' => 'sweep',
                        'endpoint' => '/api/logs/transactions',
                    ],
                    'breakdown' => [
                        'by_network' => $byNetwork,
                        'by_symbol' => $bySymbol,
                        'by_type' => $byType,
                    ],
                    'samples' => [
                        'created' => $createdSamples,
                        'updated' => $updatedSamples,
                    ],
                    'started_at' => $startedAt->toDateTimeString(),
                    'last_synced_at' => $finishedAt->toDateTimeString(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Sweeper transaction sync exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطای داخلی هنگام همگام‌سازی: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function createAccountingTransactions(): JsonResponse
    {
        $adminId = auth('admin')->id();
        $result = $this->createAccountingTransactionsService->createForPendingLogs($adminId);

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'ثبت تراکنش‌ها انجام شد. جدید: %d | ردشده: %d | ناموفق: %d',
                $result['created'],
                $result['skipped'],
                $result['failed']
            ),
            'data' => $result + [
                'pending_accounting' => SweeperTransactionLog::query()
                    ->where(function ($query) {
                        $query->whereNull('withdrawal_transaction_id')
                            ->orWhereNull('fee_transaction_id');
                    })
                    ->count(),
                'accounted' => SweeperTransactionLog::query()
                    ->whereNotNull('withdrawal_transaction_id')
                    ->whereNotNull('fee_transaction_id')
                    ->count(),
            ],
        ]);
    }

    public function createAccountingTransaction(SweeperTransactionLog $sweeperTransaction): JsonResponse
    {
        try {
            $result = $this->createAccountingTransactionsService->createForLog(
                $sweeperTransaction,
                auth('admin')->id()
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @param  array<string, mixed>  $tx
     */
    private function upsertTransaction(array $tx): string
    {
        $sweeperId = (string) ($tx['_id'] ?? $tx['id'] ?? '');
        if ($sweeperId === '') {
            return 'skipped';
        }

        $hdWalletInfo = is_array($tx['hdWalletInfo'] ?? null) ? $tx['hdWalletInfo'] : [];
        $attributes = [
            'tx_hash' => $tx['txHash'] ?? null,
            'network' => $tx['network'] ?? null,
            'symbol' => isset($tx['symbol']) ? strtoupper((string) $tx['symbol']) : null,
            'wallet_id' => $tx['walletId'] ?? ($hdWalletInfo['walletId'] ?? null),
            'address_index' => $tx['addressIndex'] ?? ($hdWalletInfo['addressIndex'] ?? null),
            'type' => $tx['type'] ?? null,
            'coin_type' => $tx['coinType'] ?? null,
            'from_address' => $tx['fromAddress'] ?? null,
            'to_address' => $tx['toAddress'] ?? null,
            'derivation_path' => $hdWalletInfo['derivationPath'] ?? null,
            'amount' => isset($tx['amount']) ? (string) $tx['amount'] : null,
            'amount_in_wei' => isset($tx['amountInWei']) ? (string) $tx['amountInWei'] : null,
            'amount_in_satoshi' => isset($tx['amountInSatoshi']) ? (string) $tx['amountInSatoshi'] : null,
            'fee' => isset($tx['fee']) ? (string) $tx['fee'] : null,
            'fee_in_wei' => isset($tx['feeInWei']) ? (string) $tx['feeInWei'] : null,
            'fee_in_satoshi' => isset($tx['feeInSatoshi']) ? (string) $tx['feeInSatoshi'] : null,
            'gas_used' => isset($tx['gasUsed']) ? (string) $tx['gasUsed'] : null,
            'gas_price' => isset($tx['gasPrice']) ? (string) $tx['gasPrice'] : null,
            'status' => $tx['status'] ?? 'broadcasted',
            'confirmations' => (int) ($tx['confirmations'] ?? 0),
            'required_confirmations' => isset($tx['requiredConfirmations']) ? (int) $tx['requiredConfirmations'] : null,
            'block_number' => isset($tx['blockNumber']) ? (int) $tx['blockNumber'] : null,
            'block_hash' => $tx['blockHash'] ?? null,
            'broadcast_at' => $this->parseDate($tx['broadcastAt'] ?? null),
            'confirmed_at' => $this->parseDate($tx['confirmedAt'] ?? null),
            'sweeper_created_at' => $this->parseDate($tx['createdAt'] ?? null),
            'sweeper_updated_at' => $this->parseDate($tx['updatedAt'] ?? null),
            'synced_at' => now(),
            'error' => $tx['error'] ?? null,
            'metadata' => $tx['metadata'] ?? null,
            'raw_payload' => $tx,
        ];

        $existing = SweeperTransactionLog::query()->where('sweeper_id', $sweeperId)->first();
        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        SweeperTransactionLog::create(array_merge(['sweeper_id' => $sweeperId], $attributes));

        return 'created';
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDuration(int $durationMs): string
    {
        if ($durationMs < 1000) {
            return $durationMs . 'ms';
        }

        $seconds = round($durationMs / 1000, 1);

        return $seconds . 's';
    }
}
