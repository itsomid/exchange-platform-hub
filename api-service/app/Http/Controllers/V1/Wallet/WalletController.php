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
        private readonly WalletService $service,
        private readonly DepositService $depositService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/wallets/generate-address",
     *     summary="Generate Wallet Address",
     *     description="Generates a wallet address for a specific currency and chain.",
     *     operationId="generateAddress",
     *     tags={"Wallet"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/GenerateAddressRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Wallet address generated successfully.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", ref="#/components/schemas/CoinAddressResponse")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="An error occurred.")
     *         )
     *     )
     * )
     */
    public function generateAddress(GenerateAddressRequest $request)
    {
        $validated = $request->validated();
        $userId = Auth::id();

        $res = $this->service->generateAddress(
            resolve(GenerateAddressRequestDTO::class)
                ->setUserId($userId)
                ->setCurrency($validated['currency'])
                ->setChainSymbol($validated['chain'])
        );

        return response([
            'data' => new CoinAddressResource($res),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/wallets/lists",
     *     summary="List User Wallets",
     *     description="Retrieve a list of the authenticated user's wallets. Displays wallet balances and their values in USDT.",
     *     operationId="listUserWallets",
     *     tags={"Wallet"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of wallets retrieved successfully.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *              @OA\Property(
     *               property="data",
     *               type="array",
     *
     *           @OA\Items(ref="#/components/schemas/WalletListsListCollection")
     *           )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function lists()
    {
        $responseDTO = $this->service->getLists(
            resolve(WalletListsRequestDTO::class)
                ->setUserId(Auth::id())
        );

        return new WalletListsCollection($responseDTO);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/wallets/{currencySymbol}",
     *     summary="Get Wallet Balance",
     *     tags={"Wallet"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="currencySymbol",
     *         in="path",
     *         description="Currency symbol to fetch the wallet details.",
     *         required=true,
     *
     *         @OA\Schema(type="string", example="BTC")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Wallet details retrieved successfully.",
     *
     *         @OA\JsonContent(ref="#/components/schemas/GetOneWalletResponse")
     *     ),
     * )
     */
    public function show(GetOneWalletRequest $request)
    {
        $symbol = $request->input('currencySymbol');
        $walletDTO = $this->service->getWallet(
            resolve(GetOneWalletRequestDTO::class)
                ->setCurrencySymbol($symbol)
                ->setUserId(Auth::id())
        );

        return new GetOneWalletResource($walletDTO);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/wallets/value-usdt",
     *     summary="Get total assets value in USDT",
     *     description="Calculate and retrieve the total value of all user assets converted to USDT.",
     *     tags={"Wallet"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Total value of assets in USDT.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="value_usdt",
     *                     type="number",
     *                     format="float",
     *                     description="Total value of assets in USDT.",
     *                     example=12345.67
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, user is not authenticated.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function assetsUSDTValue()
    {
        $assetDTO = $this->service->walletUSDTValue(
            resolve(WalletValueUSDTRequestDTO::class)
                ->setUserId(Auth::id())
        );

        return response([
            'data' => [
                'value_usdt' => $assetDTO->getAmount(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/wallets/refresh",
     *     summary="Refresh Wallet",
     *     description="Refreshes the user's wallet by checking for new deposits on a specific currency and chain.",
     *     tags={"Wallet"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/RefreshWalletRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Wallet refreshed successfully.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Wallet refreshed successfully."
     *             ),
     *                  @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  @OA\Property(
     *                  property="available_in",
     *                  type="boolean",
     *                  description="This api lock in specific currency chain until available_in",
     *                  example="2025-01-11 15:48:04"
     *              ),
     *                       @OA\Property(
     *                   property="has_new_transaction",
     *                   type="boolean",
     *                   description="is found new transactions ?",
     *                   example="true",
     *               ),
     *              ),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed for the request.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The given data was invalid."
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="currency_symbol",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The selected currency symbol is invalid.")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="chain_symbol",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The selected chain symbol is invalid.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
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
