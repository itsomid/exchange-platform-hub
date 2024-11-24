<?php

namespace App\ExternalServices\Request;

abstract class Request
{
    public static function get($url, array $body)
    {
        $client = new \GuzzleHttp\Client;
        $response = $client->get($url,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'query' => $body,
            ]
        );

        return json_decode($response->getBody());
    }

    public static function post() {}

    public static function patch($url, $body)
    {
        $client = new \GuzzleHttp\Client;
        $response = $client->patch($url,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'json' => $body,
            ]
        );

        return json_decode($response->getBody());
    }

    public static function delete($url)
    {
        $client = new \GuzzleHttp\Client;
        $response = $client->delete($url,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody());
    }
}
