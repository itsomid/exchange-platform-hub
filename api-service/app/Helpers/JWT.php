<?php

namespace App\Helpers;

use Firebase\JWT\JWT as JWTLib;
use Firebase\JWT\Key;
use stdClass;
use Throwable;

class JWT
{
    private array $payload;

    const string Algorithm = 'HS256';

    public static function new(): self
    {
        return new self;
    }

    public function encode(): string
    {
        return JWTLib::encode($this->payload, $this->getPrivateKey(), self::Algorithm);
    }

    public function decode($jwt): stdClass|false
    {
        try {
            return JWTLib::decode($jwt, new Key($this->getPrivateKey(), self::Algorithm));
        } catch (Throwable $e) {
            return false;
        }
    }

    private function getPrivateKey(): string
    {
        return config('accounting.auth.jwt_key');
    }

    public function payload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }
}
