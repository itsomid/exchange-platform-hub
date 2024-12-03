<?php

namespace App\Http\Resources\V1\Wallet;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="CoinAddressResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="address",
 *         type="string",
 *         example="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa",
 *         description="The generated wallet address for the user."
 *     )
 *      @OA\Property(
 *          property="valid_until",
 *          type="string",
 *          example="2025-01-12 15:05:01",
 *          description="The addresss expiration date"
 *      )
 * )
 * @method string getAddress()
 */
class CoinAddressResource extends JsonResource
{
    public function __construct($resource, private readonly Carbon $expirationDate)
    {

        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'address' => $this->getAddress(),
            'valid_until' => $this->expirationDate->format('Y-m-d H:i:s'),
        ];
    }
}
