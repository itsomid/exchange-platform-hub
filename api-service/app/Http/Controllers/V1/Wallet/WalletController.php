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


    public function lists()
    {
        $responseDTO = $this->walletService->getLists(
            resolve(WalletListsRequestDTO::class)
                ->setUserId(Auth::id())
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
}
