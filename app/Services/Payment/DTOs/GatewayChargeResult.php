<?php

namespace App\Services\Payment\DTOs;

class GatewayChargeResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalId = null,
        public readonly ?string $message = null,
    ) {
    }

    public static function success(string $externalId): self
    {
        return new self(true, $externalId, null);
    }

    public static function failure(?string $message = null): self
    {
        return new self(false, null, $message);
    }
}

