<?php

namespace App\Http\Controllers\External\V1\HDWallet;

use App\Enums\DepositStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\DepositDetected;
use App\Helpers\Math;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Notifications\DepositSuccessful;
use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\DepositRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DepositWebhookController extends Controller
{
    public function __construct(
        private readonly DepositRepositoryInterface $depositRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Receive deposit notification from hd-wallet-service
     * POST /api/hdwallet/deposit/notify
     */
    public function notify(Request $request)
    {
        // Validate API key from wallet service
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey || $apiKey !== config('hd-wallet.api_key')) {
            Log::channel('hd-wallet')->warning('Deposit webhook: invalid API key');
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'type' => 'required|string|in:deposit',
            'userId' => 'required',
            'network' => 'required|string',
            'currency' => 'required|string',
            'amount' => 'required|string',
            'txHash' => 'required|string',
            'toAddress' => 'required|string',
        ]);

        $data = $request->all();
        $userId = (int) $data['userId'];
        $txHash = $data['txHash'];
        $currencySymbol = $data['currency'];

        Log::channel('hd-wallet')->info('Deposit webhook received', [
            'userId' => $userId,
            'txHash' => $txHash,
            'currency' => $currencySymbol,
            'amount' => $data['amount'],
        ]);

        // Check if deposit already exists
        if ($this->depositRepository->isDepositExists($txHash)) {
            
            // Still broadcast the event so frontend gets notified
         
            DepositDetected::dispatch($userId, [
                'currency' => $currencySymbol,
                'amount' => $data['amount'],
                'tx_hash' => $txHash,
                'status' => 'already_exists',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Deposit already processed',
            ]);
        }

        try {
            $user = $this->userRepository->getUserById($userId);
            $wallet = $this->walletRepository->getOneByCurrency($currencySymbol, $userId);

            if (!$user || !$wallet) {
                Log::channel('hd-wallet')->warning('Deposit webhook: user or wallet not found', [
                    'userId' => $userId,
                    'currency' => $currencySymbol,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'User or wallet not found',
                ], 404);
            }

            $wallet->load('chains.wallet.currency.chains');

            // Find the matching currency chain
            $currencyChain = null;
            foreach ($wallet->chains as $walletChain) {
                if (strtolower($walletChain->address) === strtolower($data['toAddress'])) {
                    $currencyChain = $walletChain->wallet->currency->chains
                        ->where('chain', $walletChain->currency_chain)->first();
                    break;
                }
            }

            if (!$currencyChain) {
                // Try to find any matching currency chain for this currency
                $currency = Currency::whereSymbol($currencySymbol)->first();
                if ($currency) {
                    $currencyChain = $currency->chains->first();
                }
            }

            DB::beginTransaction();

            $depositStatus = DepositStatusEnum::CONFIRMED;
            if ($currencyChain && Math::comp($data['amount'], $currencyChain->min_deposit_amount) === -1) {
                $depositStatus = DepositStatusEnum::TOO_SMALL;
            }

            $currency = Currency::whereSymbol($currencySymbol)->first();
            $usdtValue = Math::mul($currency->exchangePrice ?? 0, $data['amount']);

            $deposit = $this->depositRepository->create(
                resolve(CreateDepositRequestDTO::class)
                    ->setUserId($userId)
                    ->setCurrencySymbol($currencySymbol)
                    ->setCurrencyChainId($currencyChain?->id)
                    ->setAmount($data['amount'])
                    ->setAddress($data['toAddress'])
                    ->setTransactionHash($txHash)
                    ->setConfirmedAt(isset($data['detectedAt']) ? \Carbon\Carbon::parse($data['detectedAt']) : now())
                    ->setStatus($depositStatus)
                    ->setUsdtValue($usdtValue)
            );

            $this->transactionRepository->create(
                resolve(CreateTransactionRequestDTO::class)
                    ->setUserId($userId)
                    ->setDepositId($deposit->id)
                    ->setWalletId($wallet->id)
                    ->setBalance($wallet->balance)
                    ->setAmount($data['amount'])
                    ->setCoinPrice($currency->exchangePrice ?? 0)
                    ->setExchangeId(null)
                    ->setType(TransactionTypeEnum::DEPOSIT)
                    ->setSubtype(TransactionSubTypeEnum::USER_INITIATED)
                    ->setStatus(TransactionStatusEnum::SUCCESS)
                    ->setDescription('واریز به آدرس: ' . $data['toAddress'] . ' هش تراکنش: ' . $txHash)
            );

            if ($depositStatus === DepositStatusEnum::CONFIRMED) {
                $wallet->increment('balance', $data['amount']);
                $user->notify(new DepositSuccessful($currencySymbol, $data['amount'], $user->name));
            }

            DB::commit();

            // Broadcast via WebSocket
   
            DepositDetected::dispatch($userId, [
                'currency' => $currencySymbol,
                'amount' => $data['amount'],
                'tx_hash' => $txHash,
                'status' => $depositStatus->value ?? 'confirmed',
            ]);

            Log::channel('hd-wallet')->info('Deposit webhook processed successfully', [
                'userId' => $userId,
                'txHash' => $txHash,
                'amount' => $data['amount'],
                'status' => $depositStatus,
            ]);

            return response()->json([
                'success' => true,
                'transactionId' => $deposit->id,
            ]);
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);

            Log::channel('hd-wallet')->error('Deposit webhook failed', [
                'userId' => $userId,
                'txHash' => $txHash,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal error',
            ], 500);
        }
    }
}
