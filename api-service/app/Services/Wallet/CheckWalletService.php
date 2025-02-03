<?php

namespace App\Services\Wallet;

use App\Enums\DepositStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Exceptions\V1\Wallet\UserDoesNotHaveWalletAddress;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsRequestDTO;
use App\Infrastructure\HDWallet\HDWalletDepositService;
use App\Notifications\DepositSuccessful;
use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\DepositRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Wallet\DTO\CheckWallet\CheckUserDepositRequestDTO;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckWalletService
{
    public function __construct(
        private readonly DepositRepositoryInterface $depositRepository,
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
        $wallet = $wallet->load('chains.wallet.currency.chains');
        $chains = $wallet->chains;
        if (! $chains->contains(fn ($chain) => ! empty($chain->address))) {
            throw new UserDoesNotHaveWalletAddress;
        }

        $hdDeposit = resolve(HDWalletDepositService::class);

        foreach ($chains as $chain) {
            if (empty($chain->address)) {
                continue;
            }

            $blockchainName = $chain->wallet->currency->chains->where('chain', $chain->currency_chain)->first()->blockchain_name->value;
            $transactions = $hdDeposit->getDepositLists(
                resolve(GetDepositListsRequestDTO::class)
                    ->setCurrencySymbol($wallet->currency_symbol)
                    ->setWalletAddress($chain->address)
                    ->setBlockchain($blockchainName)
            );
            foreach ($transactions as $transaction) {
                if ($this->depositRepository->isDepositExists($transaction->getTransactionHash())) {
                    continue;
                }
                $transactionHash = $transaction->getTransactionHash();
                try {
                    DB::beginTransaction();
                    $deposit = $this->depositRepository->create(resolve(CreateDepositRequestDTO::class)
                        ->setUserId($transaction->getUserId())
                        ->setCurrencySymbol($transaction->getCryptocurrency())
                        ->setCurrencyChain($transaction->getBlockChain())
                        ->setAmount($transaction->getAmount())
                        ->setAddress($transaction->getWalletAddress())
                        ->setTransactionHash($transaction->getTransactionHash())
                        ->setConfirmedAt($transaction->getTimestamp())
                        ->setStatus(DepositStatusEnum::CONFIRMED)
                    );

                    $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                        ->setUserId($requestDTO->getUserId())
                        ->setDepositId($deposit->id)
                        ->setWalletId($wallet->id)
                        ->setBalance($wallet->balance)
                        ->setAmount($transaction->getAmount())
                        ->setType(TransactionTypeEnum::DEPOSIT)
                        ->setSubtype(TransactionSubTypeEnum::USER_INITIATED)
                        ->setStatus(TransactionStatusEnum::SUCCESS)
                        ->setDescription('واریز به آدرس: '.$deposit->address.' هش تراکنش: '.$transactionHash)
                    );
                    $wallet->increment('balance', $transaction->getAmount());
                    $user->notify(new DepositSuccessful($transaction->getCryptocurrency(), $transaction->getAmount(), $user->name));
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

        $hdDeposit = resolve(HDWalletDepositService::class);
        foreach ($pendingDeposits as $deposit) {

            $transactions = $hdDeposit->getDepositLists(
                resolve(GetDepositListsRequestDTO::class)
                    ->setCurrencySymbol($deposit->currency_symbol)
                    ->setWalletAddress($deposit->address)
            );

            foreach ($transactions as $transaction) {
                if ($this->depositRepository->isDepositExists($transaction->getTransactionHash())) {
                    continue;
                }
                $this->depositRepository->create(resolve(CreateDepositRequestDTO::class)
                    ->setUserId($transaction->getUserId())
                    ->setCurrencySymbol($transaction->getCryptocurrency())
                    ->setCurrencyChain($transaction->getBlockChain())
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
