<?php

namespace App\Services\Wallet;

use App\Enums\DepositStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\DepositDetected;
use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Exceptions\V1\Wallet\UserDoesNotHaveWalletAddress;
use App\Exceptions\V1\Wallet\UserDoesNotHaveWalletChainAddress;
use App\Helpers\Math;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsRequestDTO;
use App\Infrastructure\HDWalletNew\HDWalletFacade;
use App\Models\Currency;
use App\Notifications\DepositSuccessful;
use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\ExchangeRepository;
use App\Repositories\Interfaces\DepositRepositoryInterface;
use App\Repositories\Interfaces\ExchangeRepositoryInterface;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Repositories\MarketRepository;
use App\Services\Wallet\DTO\CheckWallet\CheckUserDepositRequestDTO;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckWalletService
{
    public function __construct(
        private readonly DepositRepositoryInterface $depositRepository,
        private readonly ExchangeRepositoryInterface $exchangeRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @throws InternalWalletHasProblemException
     */
    public function checkUserDeposit(CheckUserDepositRequestDTO $requestDTO): bool
    {

        $hasNewTransaction = false;
        $user = $this->userRepository->getUserById($requestDTO->getUserId());
        $wallet = $this->walletRepository->getOneByCurrency($requestDTO->getCurrencySymbol(), $requestDTO->getUserId());


        if (is_null($wallet)) {

            throw new UserDoesNotHaveWalletAddress;
        }
        $wallet = $wallet->load('chains.wallet.currency.chains');

        $walletChains = $wallet->chains;

        if (! $walletChains->contains(fn($chain) => ! empty($chain->address))) {
            throw new UserDoesNotHaveWalletChainAddress;
        }

        $hdWalletService = resolve(HDWalletFacade::class);

        foreach ($walletChains as $walletChain) {
            if (empty($walletChain->address)) {
                continue;
            }

            $currencyChain = $walletChain->wallet->currency->chains->where('chain', $walletChain->currency_chain)->first();
      
            // Check if deposit is enabled for this currency chain
            if (!$currencyChain || !$currencyChain->deposit_enabled) {
                continue;
            }

            $transactions = $hdWalletService->getDepositLists(
                resolve(GetDepositListsRequestDTO::class)
                    ->setCurrencySymbol($wallet->currency_symbol)
                    ->setWalletAddress($walletChain->address)
                    ->setBlockchain($currencyChain->blockchain_name)
                    ->setContractAddress($currencyChain->contract_address ?? null)
            );
            
            foreach ($transactions as $transaction) {
                if ($this->depositRepository->isDepositExists($transaction->getTransactionHash())) {
                    continue;
                }
                $transactionHash = $transaction->getTransactionHash();
                try {
                    DB::beginTransaction();

                    $depositStatus = DepositStatusEnum::CONFIRMED;
                    if (Math::comp($transaction->getAmount(), $currencyChain->min_deposit_amount) === -1) {
                        $depositStatus = DepositStatusEnum::TOO_SMALL;
                    }

                    $currency = Currency::whereSymbol($transaction->getCryptocurrency())->first();
                    $usdtValue = Math::mul($currency->exchangePrice, $transaction->getAmount());

                    $deposit = $this->depositRepository->create(
                        resolve(CreateDepositRequestDTO::class)
                            ->setUserId($user->id)
                            ->setCurrencySymbol($transaction->getCryptocurrency())
                            ->setCurrencyChainId($currencyChain->id)
                            ->setAmount($transaction->getAmount())
                            ->setAddress($transaction->getWalletAddress())
                            ->setTransactionHash($transaction->getTransactionHash())
                            ->setConfirmedAt($transaction->getTimestamp())
                            ->setStatus($depositStatus)
                            ->setUsdtValue($usdtValue)
                    );

                    $this->transactionRepository->create(
                        resolve(CreateTransactionRequestDTO::class)
                            ->setUserId($requestDTO->getUserId())
                            ->setDepositId($deposit->id)
                            ->setWalletId($wallet->id)
                            ->setBalance($wallet->balance)
                            ->setAmount($transaction->getAmount())
                            ->setCoinPrice($currency->exchangePrice)
                            ->setExchangeId(null)
                            ->setType(TransactionTypeEnum::DEPOSIT)
                            ->setSubtype(TransactionSubTypeEnum::USER_INITIATED)
                            ->setStatus(TransactionStatusEnum::SUCCESS)
                            ->setDescription('واریز به آدرس: ' . $deposit->address . ' هش تراکنش: ' . $transactionHash)
                    );
                    if ($depositStatus === DepositStatusEnum::CONFIRMED) {
                        $wallet->increment('balance', $transaction->getAmount());
                        $user->notify(new DepositSuccessful($transaction->getCryptocurrency(), $transaction->getAmount(), $user->name));

                        // Broadcast deposit detected event via WebSocket
                        if($hdWalletService->isNewSystem()){
                            DepositDetected::dispatch($user->id, [
                                'currency' => $transaction->getCryptocurrency(),
                                'amount' => $transaction->getAmount(),
                                'tx_hash' => $transaction->getTransactionHash(),
                                'status' => 'confirmed',
                            ]);
                        }
                       
                    }
                    DB::commit();
                    $hasNewTransaction = true;
                } catch (Throwable $exception) {
                    DB::rollBack();
                    report($exception);
                    throw $exception;
                }
            }
        }

        return $hasNewTransaction;
    }

    public function checkDepositWallet()
    {
        $pendingDeposits = $this->depositRepository->getPendingDeposits();

        $hdWalletService = resolve(HDWalletFacade::class);
        foreach ($pendingDeposits as $deposit) {

            $transactions = $hdWalletService->getDepositLists(
                resolve(GetDepositListsRequestDTO::class)
                    ->setCurrencySymbol($deposit->currency_symbol)
                    ->setWalletAddress($deposit->address)
            );

            foreach ($transactions as $transaction) {
                if ($this->depositRepository->isDepositExists($transaction->getTransactionHash())) {
                    continue;
                }
                $this->depositRepository->create(
                    resolve(CreateDepositRequestDTO::class)
                        ->setUserId($transaction->getUserId())
                        ->setCurrencySymbol($transaction->getCryptocurrency())
                        ->setCurrencyChainId($transaction->getBlockChain())
                        ->setAmount($transaction->getAmount())
                        ->setAddress($transaction->getWalletAddress())
                        ->setTransactionHash($transaction->getTransactionHash())
                        ->setConfirmedAt($transaction->getTimestamp())
                        ->setStatus(DepositStatusEnum::CONFIRMED)
                );
            }
        }
    }
}
