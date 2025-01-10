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

    /**
     * @OA\Get(
     *     path="/api/v1/saved-addresses/addresses",
     *     summary="Get Saved Wallet Addresses",
     *     description="Retrieve a list of saved wallet addresses. Optionally, filter by blockchain chain.",
     *     tags={"Saved Addresses"},
     *
     *     @OA\Parameter(
     *         name="chain",
     *         in="query",
     *         required=false,
     *         description="Optional filter for the blockchain chain.",
     *
     *         @OA\Schema(
     *             type="string",
     *             example="BTC"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of saved wallet addresses.",
     *
     *         @OA\JsonContent(ref="#/components/schemas/SavedAddressCollection")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function lists(Request $request)
    {
        $userId = Auth::id();
        $chain = $request->query('chain'); // Optional filter for blockchain chain

        $addresses = $this->service->listAddresses($userId, $chain);

        return new SavedAddressCollection($addresses);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/saved-addresses/addresses",
     *     summary="Create a saved wallet address",
     *     description="Create a new saved wallet address for the authenticated user.",
     *     tags={"Saved Addresses"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *       @OA\JsonContent(ref="#/components/schemas/SavedAddressRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Wallet address created successfully.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Wallet address created successfully."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The given data was invalid."
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "name": {"The name field is required."},
     *                     "address": {"The address field is required."},
     *                     "chain": {"The chain field is required."}
     *                 }
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/v1/saved-addresses/addresses/{savedAddressId}",
     *     summary="Delete a saved wallet address",
     *     description="Delete a saved wallet address by its ID for the authenticated user.",
     *     tags={"Saved Addresses"},
     *
     *     @OA\Parameter(
     *         name="savedAddressId",
     *         in="path",
     *         required=true,
     *         description="The ID of the saved wallet address to delete",
     *
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Wallet address deleted successfully.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Wallet address deleted successfully."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Wallet address not found.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The requested resource was not found."
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
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
            ], 404);
        }
    }
}
