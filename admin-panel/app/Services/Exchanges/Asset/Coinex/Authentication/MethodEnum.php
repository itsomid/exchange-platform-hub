<?php

namespace App\Services\Exchanges\Asset\Coinex\Authentication;

enum MethodEnum: string
{
    case POST = "POST";
    case GET = "GET";
    case DELETE = "DELETE";
    case PATCH = "PATCH";
    case PUT = "PUT";
}
