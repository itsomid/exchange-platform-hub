<?php

namespace App\Services\Wallet;

use App\Enums\BalanceOperationEnum;
use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Infrastructure\HDWallet\Exceptions\HDDWalletUnavailable;
use App\Infrastructure\HDWallet\Exceptions\HDWalletException;
use App\Infrastructure\HDWallet\Wallet;
use App\Models\Market;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\WalletChainRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Wallet\DTO\Wallet\GenerateAddressRequestDTO;
use App\Services\Wallet\DTO\Wallet\GenerateAddressResponseDTO;
use App\Services\Wallet\DTO\Wallet\GetOneWalletRequestDTO;
use App\Services\Wallet\DTO\Wallet\GetOneWalletResponseDTO;
use App\Services\Wallet\DTO\Wallet\UpdateBalanceRequestDTO;
use App\Services\Wallet\DTO\Wallet\WalletListsResponseDTO;
use App\Services\Wallet\DTO\Wallet\WalletValueUSDTRequestDTO;
use App\Services\Wallet\DTO\Wallet\WalletValueUSDTResponseDTO;
use Illuminate\Support\Facades\DB;
use Throwable;

class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly WalletChainRepositoryInterface $walletChainRepository,
        private readonly MarketRepositoryInterface $marketRepository,
    ) {}

    /**
     * @throws InternalWalletHasProblemException
     * @throws Throwable
     * @throws HDDWalletUnavailable
     */
    public function generateAddress(GenerateAddressRequestDTO $requestDTO): GenerateAddressResponseDTO
    {

        try {
            DB::beginTransaction();
            $wallet = $this->walletRepository->createOrGetWallet(
                $requestDTO->getCurrency(),
                $requestDTO->getUserId()
            );

            $chain = $this->walletChainRepository->createOrGetChain(
                $wallet->id,
                $requestDTO->getChainSymbol()
            );

            $address = $chain->address;
            if (is_null($address)) {
                //Generate Public Key
                $hdWallet = resolve(Wallet::class);
                $blockchainName = $chain->wallet->currency->chains->where('chain', $chain->currency_chain)->first()->blockchain_name->value; //TODO
                try {
                    $address = $hdWallet->generateAddress(
                        $requestDTO->getUserId(),
                        $blockchainName
                    );
                } catch (HDWalletException $exception) {
                    report($exception);
                    throw new InternalWalletHasProblemException;
                }

                $this->walletChainRepository->savePublicKey(
                    $chain->id,
                    $address
                );
            }
            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);
            throw $exception;
        }

        return resolve(GenerateAddressResponseDTO::class)
            ->setAddress($address);
    }

    public function updateBalance(UpdateBalanceRequestDTO $requestDTO): bool
    {

        try {
            DB::beginTransaction();

            $wallet = $this->walletRepository
                ->getWalletWithLock($requestDTO->getCurrencySymbol(), $requestDTO->getUserId());

            $balance = $wallet->balance;
            if ($requestDTO->getOperation() === BalanceOperationEnum::Increase) {
                $balance = bcadd($wallet->balance, $requestDTO->getAmount(), 8);
            } elseif ($requestDTO->getOperation() === BalanceOperationEnum::Decrease) {
                $balance = bcsub($wallet->balance, $requestDTO->getAmount(), 8);
            }

            $this->walletRepository->updateBalance($requestDTO->getCurrencySymbol(), $requestDTO->getUserId(), $balance);

            DB::commit();

            return true;
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return false;
        }

    }

    public function getLists(DTO\Wallet\WalletListsRequestDTO $requestDTO): array
    {
        $wallets = $this->walletRepository->getLists($requestDTO->getUserId());
        $markets = $this->marketRepository->getAll();

        $lists = [];

        $wallets = $wallets->keyBy('currency_symbol');

        foreach ($markets as $market) {
            $wallet = $wallets[$market->base_currency] ?? null;
            $lists[] = $this->SetWalletDTP($wallet, $market);
        }

        //USDT
        $wallet = $wallets['USDT'] ?? null;
        $lists[] = $this->SetWalletDTP($wallet);

        return $lists;
    }

    public function getWallet(GetOneWalletRequestDTO $requestDTO): GetOneWalletResponseDTO
    {
        $wallet = $this->walletRepository->getOrCreateWallet(
            $requestDTO->getUserId(),
            $requestDTO->getCurrencySymbol()
        );

        return resolve(GetOneWalletResponseDTO::class)
            ->setCurrencySymbol($wallet->currency_symbol)
            ->setBalance($wallet->balance)
            ->setLockedBalance($wallet->locked_balance)
            ->setUsdtBalance(
                $wallet->exchangePrice ?
                    bcmul($wallet->exchangePrice->price, $wallet->balance, 8) : $wallet->balance
            )
            ->setUsdtLockedBalance(
                $wallet->exchangePrice ?
                    bcmul($wallet->exchangePrice->price, $wallet->locked_balance, 8) : $wallet->locked_balance
            );
    }

    public function walletUSDTValue(WalletValueUSDTRequestDTO $requestDTO)
    {
        $wallets = $this->walletRepository->getLists($requestDTO->getUserId());

        $sumAmount = 0;

        $wallets->map(function ($wallet) use (&$sumAmount) {
            $sumAmount += $wallet->exchangePrice ?
                bcmul($wallet->exchangePrice->price, $wallet->balance, 8) : $wallet->balance;

        });

        return resolve(WalletValueUSDTResponseDTO::class)
            ->setAmount($sumAmount);
    }

    public function SetWalletDTP(?\App\Models\Wallet $wallet, ?Market $market = null): WalletListsResponseDTO
    {
        return resolve(WalletListsResponseDTO::class)
            ->setId($wallet->id ?? null)
            ->setCurrency($wallet->currency_symbol ?? $market->base_currency)
            ->setBalance($wallet->balance ?? 0)
            ->setLockedBalance($wallet->locked_balance ?? 0)
            ->setUsdtBalance(
                $wallet && $wallet->exchangePrice ?
                    bcmul($wallet->exchangePrice->price, $wallet->balance ?? 0, 8) : $wallet->balance
            )
            ->setUsdtLockedBalance(
                $wallet && $wallet->exchangePrice ?
                    bcmul($wallet->exchangePrice->price, $wallet->locked_balance ?? 0, 8) : $wallet->locked_balance
            );
    }
}
