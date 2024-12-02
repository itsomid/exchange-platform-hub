<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Mews\Captcha\Facades\Captcha;

class CaptchaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/captcha",
     *     summary="Get CAPTCHA",
     *     description="Retrieve a CAPTCHA image and its associated key for user verification.",
     *     operationId="getCaptcha",
     *     tags={"Captcha"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="CAPTCHA retrieved successfully.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="is_fishy",
     *                 type="boolean",
     *                 example=true,
     *                 description="Indicates if the CAPTCHA generation triggered anti-fraud logic."
     *             ),
     *             @OA\Property(
     *                 property="image",
     *                 type="string",
     *                 format="binary",
     *                 example="<svg xmlns='http://www.w3.org/2000/svg' ...>",
     *                 description="The CAPTCHA image in SVG format."
     *             ),
     *             @OA\Property(
     *                 property="captcha_key",
     *                 type="string",
     *                 example="k39d8flc",
     *                 description="The unique key associated with the CAPTCHA."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unable to generate CAPTCHA.")
     *         )
     *     )
     * )
     */
    public function __invoke()
    {
        $captcha = Captcha::create(config: 'flat', api: true);
        $response = ['is_fishy' => true,  'image' => $captcha['img'], 'captcha_key' => $captcha['key']];

        return response($response, Response::HTTP_OK);
    }
}
