<?php

namespace App\Http\Controllers\V1\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Wallet\SavedAddressRequest;
use App\Http\Resources\V1\Wallet\SavedAddressCollection;
use App\Services\Wallet\SavedAddressService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedAddressController extends Controller
{
    public function __construct(private readonly SavedAddressService $service) {}

    public function lists(Request $request)
    {
        $userId = Auth::id();
        $chain = $request->query('chain'); // Optional filter for blockchain chain

        $addresses = $this->service->listAddresses($userId, $chain);

        return new SavedAddressCollection($addresses);
    }
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

    public function delete(int $savedAddressId)
    {
        try {
            $this->service->deleteAddress(userId: Auth::id(), savedAddressId: $savedAddressId);
            return response([
                'message' => __('messages.deleted_succeed'),
            ]);
        } catch (ModelNotFoundException $e) {

            return response([
                'message' => __('messages.not_found'),
            ],404);
        }
    }
}
