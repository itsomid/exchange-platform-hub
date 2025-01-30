<?php

namespace App\Services\Exchanges\Asset\Coinex\Authentication;

class CoinexAuthentication
{
    public static function getSigned(MethodEnum $method, string $path, int $timestamp, ?array $data = null): string
    {
        if (count($data)) {
            $path .= json_encode($data);
        }
        $preparedStr = $method->value.$path.$timestamp;
//dd($preparedStr);
        //signed_str = hmac.new(bytes(secret_key, 'latin-1'), msg=bytes(prepared_str, 'latin-1'), digestmod=hashlib.sha256).hexdigest().lower()
        return strtolower(hash_hmac('sha256', $preparedStr, config('exchanges.coinex.secret_key')));
    }
}
