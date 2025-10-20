<?php

namespace App\Http\Controllers\V1\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Deposit\DepositListCollection;
use App\Repositories\Interfaces\DepositRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepositController extends Controller
{
    public function __construct(
        private readonly DepositRepositoryInterface $depositRepository,
    ) {}

    public function lists(Request $request)
    {
        $request->validate([
            'currency_symbol' => 'nullable|string',
            'status' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $deposits = $this->depositRepository->getDepositsPaginated(
            userId: Auth::id(),
            currencySymbol: $request->get('currency_symbol'),
            status: $request->get('status'),
            page: $request->get('page', 1),
            perPage: $request->get('per_page', 10)
        );

        return new DepositListCollection($deposits);
    }
}
