<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Mews\Captcha\Facades\Captcha;

class CaptchaController extends Controller
{
    public function __invoke()
    {
        $captcha = Captcha::create(config: 'flat', api: true);
        $response = ['is_fishy' => true,  'image' => $captcha['img'], 'captcha_key' => $captcha['key']];

        return response($response, Response::HTTP_OK);
    }
}
