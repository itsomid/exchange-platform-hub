<?php

namespace App\Services;

use Firebase\JWT\JWT as JWTLib;
use Firebase\JWT\Key;

class JWT
{
    private $payload;

    const Algorithm = 'HS256';

    public static function new(): self
    {
        return new self;
    }

    public function encode(): string
    {
        return JWTLib::encode($this->payload, $this->getPrivateKey(), self::Algorithm);
    }

    public function decode($jwt): mixed
    {
        try {
            return JWTLib::decode($jwt, new Key($this->getPrivateKey(), self::Algorithm));
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getPrivateKey()
    {
        return config('services.jwt.key');
    }

    public function payload($payload): self
    {
        $this->payload = $payload;

        return $this;
    }
}
