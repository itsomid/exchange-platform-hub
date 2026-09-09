<?php

namespace App\Services\Sweeper;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\SweeperTransactionLog;
use App\Models\Transaction;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateSweeperAccountingTransactionsService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository
    ) {
    }

    /**
     * @return array{status: string, message: string, withdrawal_transaction_id?: int, fee_transaction_id?: int}
     */
    public function createForLog(SweeperTransactionLog $log, ?int $adminId = null): array
    {
        if ($log->withdrawal_transaction_id && $log->fee_transaction_id) {
            return [
                'status' => 'skipped',
                'message' => 'تراکنش‌های این رکورد قبلاً ثبت شده است.',
                'withdrawal_transaction_id' => (int) $log->withdrawal_transaction_id,
                'fee_transaction_id' => (int) $log->fee_transaction_id,
            ];
        }

        return DB::transaction(function () use ($log, $adminId) {
            $locked = SweeperTransactionLog::query()
                ->whereKey($log->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->withdrawal_transaction_id && $locked->fee_transaction_id) {
                return [
                    'status' => 'skipped',
                    'message' => 'تراکنش‌های این رکورد قبلاً ثبت شده است.',
                    'withdrawal_transaction_id' => (int) $locked->withdrawal_transaction_id,
                    'fee_transaction_id' => (int) $locked->fee_transaction_id,
                ];
            }

            $exchangeUserId = (int) config('bitexroom.user_id', 1);
            $symbol = strtoupper((string) $locked->symbol);
            $amount = $this->toDecimal($locked->amount);
            $feeAmount = $this->toDecimal($locked->fee);
            $feeSymbol = $this->resolveFeeSymbol($locked->network, $symbol);

            if ($symbol === '') {
                throw new RuntimeException('نماد ارز برای این رکورد مشخص نیست.');
            }

            $amountWallet = $this->walletRepository->getExchangeWallet($symbol);
            if (!$amountWallet) {
                throw new RuntimeException("کیف پول صرافی برای نماد {$symbol} یافت نشد.");
            }

            $feeWallet = $feeSymbol === $symbol
                ? $amountWallet
                : $this->walletRepository->getExchangeWallet($feeSymbol);

            if (!$feeWallet) {
                throw new RuntimeException("کیف پول صرافی برای فی شبکه ({$feeSymbol}) یافت نشد.");
            }

            $amountCurrency = Currency::query()->where('symbol', $symbol)->first();
            $feeCurrency = $feeSymbol === $symbol
                ? $amountCurrency
                : Currency::query()->where('symbol', $feeSymbol)->first();

            $amountCoinPrice = $amountCurrency?->exchangePrice ?? 1;
            $feeCoinPrice = $feeCurrency?->exchangePrice ?? 1;

            $txHash = $locked->tx_hash ?: '—';
            $network = $locked->network ?: '—';

            $withdrawalTx = Transaction::query()->create([
                'user_id' => $exchangeUserId,
                'admin_id' => $adminId,
                'wallet_id' => $amountWallet->id,
                'amount' => -abs($amount),
                'balance' => $amountWallet->balance,
                'coin_price' => $amountCoinPrice,
                'type' => TransactionTypeEnum::WITHDRAWAL,
                'subtype' => TransactionSubTypeEnum::SWEEPER,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => sprintf(
                    'برداشت سوئیپر | sweeper_log=#%s',
                    $locked->id
                ),
                'admin_description' => 'Sweeper sweep accounting withdrawal',
                'notes' => json_encode([
                    'sweeper_transaction_log_id' => $locked->id,
                    'sweeper_id' => $locked->sweeper_id,
                    'tx_hash' => $locked->tx_hash,
                    'kind' => 'withdrawal',
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $feeTx = Transaction::query()->create([
                'user_id' => $exchangeUserId,
                'admin_id' => $adminId,
                'wallet_id' => $feeWallet->id,
                'amount' => -abs($feeAmount),
                'balance' => $feeWallet->balance,
                'coin_price' => $feeCoinPrice,
                'type' => TransactionTypeEnum::FEE,
                'subtype' => TransactionSubTypeEnum::SWEEPER,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => sprintf(
                    'کارمزد شبکه سوئیپر | sweeper_log=#%s',
                    $locked->id
                ),
                'admin_description' => 'Sweeper sweep accounting network fee',
                'notes' => json_encode([
                    'sweeper_transaction_log_id' => $locked->id,
                    'sweeper_id' => $locked->sweeper_id,
                    'tx_hash' => $locked->tx_hash,
                    'kind' => 'fee',
                    'fee_symbol' => $feeSymbol,
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $locked->update([
                'withdrawal_transaction_id' => $withdrawalTx->id,
                'fee_transaction_id' => $feeTx->id,
                'transactions_created_at' => now(),
            ]);

            return [
                'status' => 'created',
                'message' => 'دو تراکنش (برداشت + فی) با موفقیت ثبت شد.',
                'withdrawal_transaction_id' => $withdrawalTx->id,
                'fee_transaction_id' => $feeTx->id,
            ];
        });
    }

    /**
     * @return array{created: int, skipped: int, failed: int, errors: array<int, array{id: int, message: string}>}
     */
    public function createForPendingLogs(?int $adminId = null): array
    {
        $created = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        $logs = SweeperTransactionLog::query()
            ->where(function ($query) {
                $query->whereNull('withdrawal_transaction_id')
                    ->orWhereNull('fee_transaction_id');
            })
            ->orderBy('id')
            ->get();

        foreach ($logs as $log) {
            try {
                $result = $this->createForLog($log, $adminId);
                if (($result['status'] ?? '') === 'created') {
                    $created++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $failed++;
                if (count($errors) < 20) {
                    $errors[] = [
                        'id' => $log->id,
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        return compact('created', 'skipped', 'failed', 'errors');
    }

    private function resolveFeeSymbol(?string $network, string $fallbackSymbol): string
    {
        $nativeSymbol = $this->nativeSymbolForNetwork($network);
        if ($nativeSymbol) {
            return $nativeSymbol;
        }

        $blockchainName = $this->blockchainNameForNetwork($network);
        if ($blockchainName) {
            $baseChain = CurrencyChain::query()
                ->with('currency')
                ->where('blockchain_name', $blockchainName)
                ->where('is_base_coin', true)
                ->first();

            if ($baseChain?->currency?->symbol) {
                return strtoupper((string) $baseChain->currency->symbol);
            }
        }

        return $fallbackSymbol;
    }

    private function nativeSymbolForNetwork(?string $network): ?string
    {
        return match (strtolower((string) $network)) {
            'bitcoin' => 'BTC',
            'ethereum', 'optimism', 'arbitrum' => 'ETH',
            'bnb' => 'BNB',
            'tron' => 'TRX',
            'dogecoin' => 'DOGE',
            'polygon' => 'POL',
            'avalanche' => 'AVAX',
            'sonic' => 'S',
            'litecoin' => 'LTC',
            'dash' => 'DASH',
            default => null,
        };
    }

    private function blockchainNameForNetwork(?string $network): ?string
    {
        return match (strtolower((string) $network)) {
            'bitcoin' => 'BITCOIN',
            'ethereum' => 'ETHEREUM',
            'bnb' => 'BINANCE',
            'tron' => 'TRON',
            'dogecoin' => 'DOGECOIN',
            'polygon' => 'POLYGON',
            'arbitrum' => 'ARBITRUM',
            'optimism' => 'OPTIMISM',
            'avalanche' => 'AVALANCHE',
            'sonic' => 'SONIC',
            'litecoin' => 'LITECOIN',
            'dash' => 'DASH',
            default => null,
        };
    }

    private function toDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }

    private function formatAmount(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 8, '.', ''), '0'), '.') ?: '0';
    }
}
