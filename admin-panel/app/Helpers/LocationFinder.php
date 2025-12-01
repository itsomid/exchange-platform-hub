<?php

namespace App\Helpers;

class LocationFinder
{
    /**
     * Get location data including country, city, and country code
     */
    public static function getLocationData(string $ip): array
    {
        if ($ip === '127.0.0.1' || $ip === '::1' || self::isPrivateIp($ip)) {
            return [
                'country' => 'Private Network',
                'city' => 'Localhost',
                'country_code' => '',
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/json/{$ip}?fields=country,city,countryCode");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['country'])) {
            return [
                'country' => $data['country'] ?? 'Unknown',
                'city' => $data['city'] ?? '-',
                'country_code' => strtolower($data['countryCode'] ?? ''),
            ];
        }

        return [
            'country' => 'Unknown',
            'city' => '-',
            'country_code' => '',
        ];
    }

    /**
     * Get country and city as string (deprecated, use getLocationData instead)
     */
    public static function getCountryAndCity(string $ip)
    {
        if ($ip === '127.0.0.1' || $ip === '::1' || self::isPrivateIp($ip)) {
            return 'Private IP Address';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/json/{$ip}?fields=country,city");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['country']) && isset($data['city'])) {
            return $data['country'] . ', ' . $data['city'];
        }

        return 'Unknown';
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
