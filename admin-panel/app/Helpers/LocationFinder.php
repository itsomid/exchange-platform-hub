<?php

namespace App\Helpers;

class LocationFinder
{
    public static function getCountryAndCity(string $ip)
    {
        if ($ip === '127.0.0.1' || $ip === '::1' || self::isPrivateIp($ip)) {
            return 'Private IP Address'; // Handle private IPs
        }


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/json/{$ip}?fields=country,city");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['country']) && isset($data['city'])) {
            return $data['country'] . ' ' . $data['city'];
        }

        return 'Unknown'; // Return 'Unknown' if no country or city found
    }

    private static function isPrivateIp($ip)
    {
        $private_ips = [
            '10.' => true,
            '172.' => true,
            '192.' => true
        ];

        $ip_parts = explode('.', $ip);
        return isset($private_ips[$ip_parts[0].'.']) || $ip === '127.0.0.1' || $ip === '::1';
    }
}
