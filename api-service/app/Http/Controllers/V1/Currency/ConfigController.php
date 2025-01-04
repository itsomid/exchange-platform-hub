<?php

namespace App\Http\Controllers\V1\Currency;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Currency\CurrencyConfigRequest;
use App\Http\Resources\V1\Currency\ConfigCollection;
use App\Http\Resources\V1\Currency\ConfigResource;
use App\Services\Currency\CurrencyService;
use App\Services\Currency\DTO\GetConfigCurrencyRequestDTO;
use Illuminate\Http\Response;

class ConfigController extends Controller
{
    public function __construct(private readonly CurrencyService $currencyService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/deposit-withdraw/config",
     *     summary="Get Deposit and Withdrawal Configuration",
     *     description="Retrieve deposit and withdrawal configuration for a specific currency.",
     *     operationId="depositWithdrawConfig",
     *     tags={"Currency"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *        required=true,
     *
     *        @OA\JsonContent(ref="#/components/schemas/CurrencyConfigRequest")
     *    ),*
     *
     *     @OA\Response(
     *         response=200,
     *         description="Configuration retrieved successfully.",
     *
     *              @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="Registration successful."),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  ref="#/components/schemas/ConfigResponse"
     *              )
     *          )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", additionalProperties={"type": "array", "items": {"type": "string"}})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function depositWithdrawConfig(CurrencyConfigRequest $request): Response
    {
        $currency = $request->get('ccy');
        $currency = $this->currencyService->getConfig(
            resolve(GetConfigCurrencyRequestDTO::class)
                ->setSymbol($currency)
        );

        return response(new ConfigResource($currency));
    }

    /**
     * @OA\GET(
     *     path="/api/v1/currencies/all-deposit-withdraw",
     *     summary="Get Deposit and Withdrawal Configuration",
     *     description="Retrieve deposit and withdrawal configuration for all currencies.",
     *     operationId="allDepositWithdrawConfig",
     *     tags={"Currency"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Configuration retrieved successfully.",
     *
     *         @OA\JsonContent(
     *              type="array",
     *
     *              @OA\Items(ref="#/components/schemas/ConfigCollection")
     *          )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", additionalProperties={"type": "array", "items": {"type": "string"}})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function allDepositWithdrawConfig(): ConfigCollection
    {
        $currencies = $this->currencyService->getAllConfig();

        return new ConfigCollection($currencies);
    }
}
