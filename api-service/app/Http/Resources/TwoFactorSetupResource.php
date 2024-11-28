<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @method string getQrImage()
 * @method string getSecretKey()
 */
class TwoFactorSetupResource extends JsonResource
{
    public static $wrap = 'data';

    /**
     * @OA\Schema(
     *      schema="TwoFactorSetupResponse",
     *      type="object",
     *      title="TwoFactorSetupResponse",
     *      description="Response schema for 2FA setup",
     *
     *      @OA\Property(
     *          property="qr_image",
     *          type="string",
     *          format="uri",
     *          description="The URL of the QR code image for setting up 2FA."
     *      ),
     *      @OA\Property(
     *          property="secret_key",
     *          type="string",
     *          description="The secret key for setting up 2FA."
     *      )
     *  )
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'qr_image' => $this->getQrImage(),
            'secret_key' => $this->getSecretKey(),
        ];
    }
}
