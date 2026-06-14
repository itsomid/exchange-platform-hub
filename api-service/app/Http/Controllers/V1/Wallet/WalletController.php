<?php

namespace App\Http\Controllers\V1\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Wallet\GenerateAddressRequest;
use App\Http\Requests\V1\Wallet\GetOneWalletRequest;
use App\Http\Requests\V1\Wallet\RefreshWalletRequest;
use App\Http\Resources\V1\Wallet\CoinAddressResource;
use App\Http\Resources\V1\Wallet\GetOneWalletResource;
use App\Http\Resources\V1\Wallet\WalletListsCollection;
use App\Services\Wallet\CheckWalletService;
use App\Services\Wallet\DepositService;
use App\Services\Wallet\DTO\CheckWallet\CheckUserDepositRequestDTO;
use App\Services\Wallet\DTO\Wallet\GenerateAddressRequestDTO;
use App\Services\Wallet\DTO\Wallet\GetOneWalletRequestDTO;
use App\Services\Wallet\DTO\Wallet\WalletListsRequestDTO;
use App\Services\Wallet\DTO\Wallet\WalletValueUSDTRequestDTO;
use App\Services\Wallet\WalletService;
use App\Infrastructure\HDWalletNew\HDWalletFacade;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly DepositService $depositService
    ) {}


    public function generateAddress(GenerateAddressRequest $request)
    {
        $validated = $request->validated();
        $userId = Auth::id();

        $res = $this->walletService->generateAddress(
            resolve(GenerateAddressRequestDTO::class)
                ->setUserId($userId)
                ->setCurrency($validated['currency'])
                ->setChainSymbol($validated['chain'])
        );

        return response([
            'data' => new CoinAddressResource($res),
        ]);
    }


    public function lists(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => ['nullable', \Illuminate\Validation\Rule::in([20, 30, 50, 100, 'all'])],
            'hide_zero_balance' => 'nullable|boolean',
        ]);

        $fetchAll = $request->input('per_page') === 'all';
        $perPage = $fetchAll ? PHP_INT_MAX : $request->integer('per_page', 20);

        $responseDTO = $this->walletService->getLists(
            resolve(WalletListsRequestDTO::class)
                ->setUserId(Auth::id())
                ->setPage(1)
                ->setPerPage($perPage)
                ->setHideZeroBalance($request->boolean('hide_zero_balance', false))
        );

        return new WalletListsCollection($responseDTO);
    }


    public function show(GetOneWalletRequest $request)
    {
        $symbol = $request->input('currencySymbol');
        $walletDTO = $this->walletService->getWallet(
            resolve(GetOneWalletRequestDTO::class)
                ->setCurrencySymbol($symbol)
                ->setUserId(Auth::id())
        );

        return new GetOneWalletResource($walletDTO);
    }


    public function assetsUSDTValue()
    {
        $assetDTO = $this->walletService->walletUSDTValue(
            resolve(WalletValueUSDTRequestDTO::class)
                ->setUserId(Auth::id())
        );

        return response([
            'data' => [
                'value_usdt' => $assetDTO->getAmount(),
            ],
        ]);
    }


    public function refresh(RefreshWalletRequest $request)
    {
        $validatedData = $request->validated();
        $hasNewTransaction = resolve(CheckWalletService::class)
            ->checkUserDeposit(
                resolve(CheckUserDepositRequestDTO::class)
                    ->setUserId(Auth::id())
                    ->setCurrencySymbol($validatedData['currency_symbol'])
                //                    ->setCurrencyChain($validatedData['chain_symbol'])
            );

        return response([
            'message' => $hasNewTransaction ? __('messages.wallet_refresh_succeed') : __('messages.wallet_refresh'),
            'data' => [
                'available_in' => now()->addMinutes(config('bitexroom.wallet_refresh.minutes'))->format('Y-m-d H:i:s'),
                'has_new_transaction' => $hasNewTransaction,
            ],
        ]);
    }

    /**
     * Start watching a currency's chains for deposits
     * POST /api/v1/wallets/watch-deposit
     */
    public function watchDeposit(Request $request)
    {
        $request->validate([
            'currency_symbol' => 'required|string|exists:currencies,symbol',
            'chain' => 'nullable|string',
            'ttl_minutes' => 'nullable|integer|min:1|max:30',
        ]);

        $userId = Auth::id();
        $currencySymbol = $request->input('currency_symbol');
        $selectedChain = $request->input('chain');
        $ttlMinutes = $request->integer('ttl_minutes', 10);

        $wallet = resolve(WalletRepositoryInterface::class)->getOneByCurrency($currencySymbol, $userId);

        if (!$wallet) {
            return response(['message' => __('messages.wallet_not_found')], 404);
        }

        $wallet->load('chains.wallet.currency.chains');
        $walletChains = $wallet->chains;

        $hdWalletFacade = resolve(HDWalletFacade::class);
        $watchedChains = [];

        foreach ($walletChains as $walletChain) {
            if (empty($walletChain->address)) {
                continue;
            }

            // If a specific chain is selected, only watch that chain
            if ($selectedChain && $walletChain->currency_chain->value !== $selectedChain) {
                continue;
            }

            $currencyChain = $walletChain->wallet->currency->chains
                ->where('chain', $walletChain->currency_chain)->first();

            if (!$currencyChain || !$currencyChain->deposit_enabled) {
                continue;
            }

            try {
                $result = $hdWalletFacade->watchDeposit(
                    $userId,
                    $walletChain->address,
                    $currencyChain->blockchain_name->value,
                    $currencySymbol,
                    $ttlMinutes,
                );

                if ($result) {
                    $watchedChains[] = [
                        'chain' => $walletChain->currency_chain,
                        'address' => $walletChain->address,
                        'expires_at' => $result['expiresAt'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response([
            'message' => 'واریز شما در حال بررسی است',
            'data' => [
                'watched_chains' => $watchedChains,
                'ttl_minutes' => $ttlMinutes,
                'expires_at' => now()->addMinutes($ttlMinutes)->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Stop watching a currency's chains for deposits
     * DELETE /api/v1/wallets/watch-deposit
     */
    public function unwatchDeposit(Request $request)
    {
        $request->validate([
            'currency_symbol' => 'required|string',
            'chain' => 'nullable|string',
        ]);

        $userId = Auth::id();
        $currencySymbol = $request->input('currency_symbol');
        $selectedChain = $request->input('chain');

        $wallet = resolve(WalletRepositoryInterface::class)->getOneByCurrency($currencySymbol, $userId);

        if (!$wallet) {
            return response(['message' => 'ok']);
        }

        $wallet->load('chains.wallet.currency.chains');
        $hdWalletFacade = resolve(HDWalletFacade::class);

        foreach ($wallet->chains as $walletChain) {
            // If a specific chain is selected, only unwatch that chain
            if ($selectedChain && $walletChain->currency_chain->value !== $selectedChain) {
                continue;
            }

            $currencyChain = $walletChain->wallet->currency->chains
                ->where('chain', $walletChain->currency_chain)->first();

            if (!$currencyChain) continue;

            try {
                $hdWalletFacade->unwatchDeposit(
                    $userId,
                    $currencyChain->blockchain_name->value,
                    $currencySymbol,
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response(['message' => 'ok']);
    }
}
