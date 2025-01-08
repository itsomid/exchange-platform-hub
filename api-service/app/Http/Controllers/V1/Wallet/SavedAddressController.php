<?php

namespace App\Http\Controllers\V1\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Wallet\SavedAddressRequest;
use App\Services\Wallet\SaveAddressService;
use Illuminate\Support\Facades\Auth;

class SavedAddressController extends Controller
{
    public function __construct(private readonly SaveAddressService $service) {}

    public function save(SavedAddressRequest $request)
    {
        $validatedData = $request->validated();
        $this->service->saveAddress(
            userId: Auth::id(),
            chain: $validatedData['chain'],
            name: $validatedData['name'],
            address: $validatedData['address']
        );

        return response([
            'message' => __('messages.created_succeed'),
        ]);
    }

    public function delete(string $addressName)
    {
        $this->service->deleteAddress(
            userId: Auth::id(), name: $addressName
        );

    }
}
