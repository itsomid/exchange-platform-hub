<?php

namespace App\Utils;

class LocationFinder
{
    public static function getCountryAndCity(string $ip)
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $ip = '8.8.8.8'; // Use a public IP for testing (Google DNS)
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/json/{$ip}?fields=country,city");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        return $data['country'].' '.$data['city'] ?? 'Unknown';
    }
}
