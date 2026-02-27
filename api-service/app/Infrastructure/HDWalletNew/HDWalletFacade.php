<?php

namespace App\Infrastructure\HDWalletNew;

use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsRequestDTO as OldGetDepositListsRequestDTO;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsResponseDTO as OldGetDepositListsResponseDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusRequestDTO as OldGetWithdrawalStatusRequestDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusResponseDTO as OldGetWithdrawalStatusResponseDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawRequestDTO as OldWithdrawRequestDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawResponseDTO as OldWithdrawResponseDTO;
use App\Infrastructure\HDWallet\OldHDWalletService;
use App\Infrastructure\HDWalletNew\DTO\Address\GenerateAddressRequestDTO;
use App\Infrastructure\HDWalletNew\DTO\Address\GenerateAddressResponseDTO;
use App\Infrastructure\HDWalletNew\DTO\Deposit\GetDepositListsRequestDTO as NewGetDepositListsRequestDTO;
use App\Infrastructure\HDWalletNew\DTO\Deposit\GetDepositListsResponseDTO as NewGetDepositListsResponseDTO;
use App\Infrastructure\HDWalletNew\DTO\Withdrawal\GetWithdrawalStatusRequestDTO as NewGetWithdrawalStatusRequestDTO;
use App\Infrastructure\HDWalletNew\DTO\Withdrawal\GetWithdrawalStatusResponseDTO as NewGetWithdrawalStatusResponseDTO;
use App\Infrastructure\HDWalletNew\DTO\Withdrawal\WithdrawRequestDTO as NewWithdrawRequestDTO;
use App\Infrastructure\HDWalletNew\DTO\Withdrawal\WithdrawResponseDTO as NewWithdrawResponseDTO;
use App\Infrastructure\HDWalletNew\BlockchainNetworkMapper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * HDWalletFacade - Switch between Old and New HD Wallet Systems
 *
 * This facade allows you to switch between the old and new HD Wallet systems
 * using the HD_WALLET_ACTIVE_SYSTEM environment variable.
 *
 * Set HD_WALLET_ACTIVE_SYSTEM to 'old' or 'new' in your .env file
 */
class HDWalletFacade
{
    private OldHDWalletService $oldService;
    private NewHDWalletService $newService;
    private string $activeSystem;

    public function __construct()
    {
        $this->oldService = resolve(OldHDWalletService::class);
        $this->newService = resolve(NewHDWalletService::class);
        $this->activeSystem = config('hd-wallet.active_system', 'old');
    }

    /**
     * Check if new system is active
     */
    public function isNewSystem(): bool
    {
        return $this->activeSystem === 'new';
    }

    /**
     * Check if old system is active
     */
    public function isOldSystem(): bool
    {
        return $this->activeSystem === 'old';
    }

    // ──────────────────────────────────────────────
    //  Address Generation
    // ──────────────────────────────────────────────

    /**
     * Generate address using active system
     *
     * @param int $userId
     * @param string $network Network name (bitcoin, ethereum, tron, bnb, dogecoin)
     * @param string $currencySymbol Currency symbol (BTC, ETH, TRX, BNB, DOGE, USDT, etc.)
     * @return string The generated address
     */
    public function generateAddress(int $userId, string $network, string $currencySymbol): string
    {
        if ($this->isNewSystem()) {
            // Convert old blockchain name to new network name
            $newNetwork = BlockchainNetworkMapper::toNewNetwork($network);
            
            $requestDTO = resolve(GenerateAddressRequestDTO::class)
                ->setUserId($userId)
                ->setNetwork($newNetwork)
                ->setCurrencySymbol($currencySymbol);

            $response = $this->newService->generateAddress($requestDTO);
            return $response->getAddress();
        }

        // Old system
        return $this->oldService->generateAddress($userId, $network);
    }

    // ──────────────────────────────────────────────
    //  Withdrawal
    // ──────────────────────────────────────────────

    /**
     * Create withdrawal - compatible with old system interface
     *
     * Accepts old WithdrawRequestDTO and returns old WithdrawResponseDTO
     * but internally routes to new system if active
     *
     * @throws InternalWalletHasProblemException
     */
    public function withdraw(OldWithdrawRequestDTO $requestDTO): OldWithdrawResponseDTO
    {
        if ($this->isNewSystem()) {
            // Convert old blockchain name to new network name
            $newNetwork = BlockchainNetworkMapper::toNewNetwork($requestDTO->getBlockchain());
            
            $newRequestDTO = resolve(NewWithdrawRequestDTO::class)
                ->setWithdrawalId($requestDTO->getWithdrawalId())
                ->setUserId($requestDTO->getUserId())
                ->setNetwork($newNetwork)
                ->setCurrencySymbol($requestDTO->getCurrencySymbol())
                ->setAmount($requestDTO->getAmount())
                ->setToAddress($requestDTO->getWithdrawAddress());

            $newResponse = $this->newService->withdraw($newRequestDTO);

            // Map new response back to old WithdrawResponseDTO
            return resolve(OldWithdrawResponseDTO::class)
                ->setWithdrawalId($requestDTO->getWithdrawalId())
                ->setUserId($requestDTO->getUserId())
                ->setCurrencySymbol($requestDTO->getCurrencySymbol())
                ->setBlockchain($requestDTO->getBlockchain())
                ->setAmount($newResponse->getRequestedAmount())
                ->setWithdrawAddress($newResponse->getToAddress())
                ->setTransactionHash(null)
                ->setBlockNumber(null)
                ->setStatus($newResponse->getStatus())
                ->setTimestamp((string) now()->timestamp)
                ->setFee(null)
                ->setDescription(null);
        }

        return $this->oldService->withdraw($requestDTO);
    }

    /**
     * Get withdrawal status - compatible with old system interface
     *
     * Accepts old GetWithdrawalStatusRequestDTO and returns old GetWithdrawalStatusResponseDTO
     * but internally routes to new system if active
     *
     * @throws InternalWalletHasProblemException
     */
    public function getWithdrawalStatus(OldGetWithdrawalStatusRequestDTO $requestDTO): OldGetWithdrawalStatusResponseDTO
    {
        if ($this->isNewSystem()) {
            $newRequestDTO = resolve(NewGetWithdrawalStatusRequestDTO::class)
                ->setWithdrawalId((string) $requestDTO->getWithdrawalId());

            $newResponse = $this->newService->getWithdrawalStatus($newRequestDTO);

            // Map status from new to old system format
            $status = $this->mapWithdrawalStatus($newResponse->getStatus());
            
            // Convert new network name back to old blockchain name
            $oldBlockchain = BlockchainNetworkMapper::toOldBlockchain($newResponse->getNetwork());
            
            return resolve(OldGetWithdrawalStatusResponseDTO::class)
                ->setWithdrawalId($requestDTO->getWithdrawalId())
                ->setUserId((int) $newResponse->getUserId())
                ->setCurrencySymbol($newResponse->getCurrencySymbol())
                ->setBlockchain($oldBlockchain)
                ->setAmount($newResponse->getRequestedAmount())
                ->setWithdrawAddress($newResponse->getToAddress())
                ->setTransactionHash($newResponse->getTxHash())
                ->setBlockNumber(null)
                ->setStatus($status)
                ->setTimestamp($newResponse->getCreatedAt() ? (string) Carbon::parse($newResponse->getCreatedAt())->timestamp : null)
                ->setFee($newResponse->getFee())
                ->setDescription($newResponse->getFailureReason());
        }

        return $this->oldService->getWithdrawalStatus($requestDTO);
    }

    // ──────────────────────────────────────────────
    //  Deposit
    // ──────────────────────────────────────────────

    /**
     * Get deposit lists - compatible with old system interface
     *
     * Accepts old GetDepositListsRequestDTO and returns array of old GetDepositListsResponseDTO
     * but internally routes to new system if active
     *
     * @return OldGetDepositListsResponseDTO[]
     * @throws InternalWalletHasProblemException
     */
    public function getDepositLists(OldGetDepositListsRequestDTO $requestDTO): array
    {
        if ($this->isNewSystem()) {
            // We need userId. Since old system doesn't have userId in request,
            // we need to look it up from the wallet address.
            // For now, we'll use the scan-user approach from the new system or
            // fall back to getting deposits by address
            Log::channel('hd-wallet')->info('HD Wallet Facade - Using new system for deposit lists', [
                'wallet_address' => $requestDTO->getWalletAddress(),
                'network' => $requestDTO->getBlockchain(),
                'currency' => $requestDTO->getCurrencySymbol(),
            ]);

            // The new system uses userId-based deposit retrieval
            // We need to find userId from wallet address
            // This needs to be handled at the caller level
            // For backwards compatibility, we'll try to use the old system as fallback
            // or perform a model lookup
            $userAddress = \App\Models\WalletChain::where('address', $requestDTO->getWalletAddress())->first();

            if ($userAddress) {
                // Convert old blockchain name to new network name
                $newNetwork = BlockchainNetworkMapper::toNewNetwork($requestDTO->getBlockchain());
             
                $newRequestDTO = resolve(NewGetDepositListsRequestDTO::class)
                    ->setUserId((string) $userAddress->wallet->user_id)
                    ->setNetwork($newNetwork)
                    ->setCurrencySymbol($requestDTO->getCurrencySymbol())
                    ->setLimit($requestDTO->getLimit());

                $newDeposits = $this->newService->getDepositLists($newRequestDTO);
         
                // Map new response back to old GetDepositListsResponseDTO format
                return array_map(function (NewGetDepositListsResponseDTO $deposit) use ($requestDTO) {
                    return resolve(OldGetDepositListsResponseDTO::class)
                        ->setTimestamp(Carbon::parse($deposit->getCreatedAt())->timestamp)
                        ->setCryptocurrency($deposit->getCurrencySymbol())
                        ->setAmount($deposit->getAmount())
                        ->setTransactionHash($deposit->getTxHash())
                        ->setStatus($deposit->getStatus())
                        ->setConfirmationBlocks($deposit->getConfirmations())
                        ->setBlockChain(strtoupper($requestDTO->getBlockchain()))
                        ->setWalletAddress($deposit->getToAddress())
                        ->setContractAddress($deposit->getContractAddress())
                        ->setType(null)
                        ->setBlockNumber($deposit->getBlockNumber())
                        ->setFrom($deposit->getFromAddress() ?? '')
                        ->setTo($deposit->getToAddress());
                }, $newDeposits);
            }

            // If we can't find the user, log warning and fall back to old system
            Log::channel('hd-wallet')->warning('HD Wallet Facade - Could not find user for address, falling back to old system', [
                'wallet_address' => $requestDTO->getWalletAddress(),
            ]);
        }

        return $this->oldService->getDepositLists($requestDTO);
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    /**
     * Map withdrawal status from new system to old system format
     */
    private function mapWithdrawalStatus(string $newStatus): string
    {
        return match ($newStatus) {
            'pending' => 'pending',
            'processing' => 'processing',
            'completed', 'confirmed' => 'completed',
            'failed' => 'failed',
            'cancelled' => 'failed',
            default => $newStatus,
        };
    }
}
