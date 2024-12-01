<?php

namespace App\Http\Controllers\V1\Currency;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Currency\CurrencyConfigRequest;
use App\Http\Resources\V1\Currency\ConfigResource;
use App\Services\Currency\CurrencyService;
use App\Services\Currency\DTO\GetConfigCurrencyRequestDTO;
use Illuminate\Http\Response;

class ConfigController extends Controller
{
    public function __construct(private readonly CurrencyService $currencyService) {}

    public function depositWithdrawConfig(CurrencyConfigRequest $request): Response
    {
        $currency = $request->get('ccy');
        $currency = $this->currencyService->getConfig(
            resolve(GetConfigCurrencyRequestDTO::class)
                ->setSymbol($currency)
        );

        return response(new ConfigResource($currency));
    }
}
