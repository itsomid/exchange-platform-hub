<?php

namespace App\Http\Controllers\V1\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Wallet\GenerateAddressRequest;
use App\Http\Requests\V1\Wallet\GetOneWalletRequest;
use App\Http\Resources\V1\Wallet\CoinAddressResource;
use App\Http\Resources\V1\Wallet\GetOneWalletResource;
use App\Http\Resources\V1\Wallet\WalletListsCollection;
use App\Services\Wallet\DepositService;
use App\Services\Wallet\DTO\Deposit\AddPendingDepositRequestDTO;
use App\Services\Wallet\DTO\Wallet\GenerateAddressRequestDTO;
use App\Services\Wallet\DTO\Wallet\GetOneWalletRequestDTO;
use App\Services\Wallet\DTO\Wallet\WalletListsRequestDTO;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $service, private readonly DepositService $depositService) {}

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

        try {
            DB::beginTransaction();
            $res = $this->service->generateAddress(
                resolve(GenerateAddressRequestDTO::class)
                    ->setUserId($userId)
                    ->setCurrency($validated['currency'])
                    ->setChainSymbol($validated['chain'])
            );

            //Insert Pending Deposit
            $this->depositService->addPendingDeposit(
                resolve(AddPendingDepositRequestDTO::class)
                    ->setUserId($userId)
                    ->setCurrencySymbol($validated['currency'])
                    ->setCurrencyChain($validated['chain'])
                    ->setPublicKey($res->getAddress())
                    ->setExpirationDate($expirationDate = now()->addMinutes(config('bitexroom.deposit_watching_per_minutes')))
            );

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);

            return response([
                'message' => __('messages.server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response([
            'data' => new CoinAddressResource($res, $expirationDate),
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

    public function show(GetOneWalletRequest $request)
    {
        $symbol = $request->input('currencySymbol');
        $walletDTO = $this->service->getWallet(
            resolve(GetOneWalletRequestDTO::class)
                ->setSymbol($symbol)
                ->setUserId(Auth::id())
        );

        return new GetOneWalletResource($walletDTO);
    }
}
