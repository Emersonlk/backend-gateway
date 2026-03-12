<?php

namespace App\Services\Payment\DTOs;

class GatewayRefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $message = null,
    ) {
    }

    public static function success(): self
    {
        return new self(true, null);
    }

    public static function failure(?string $message = null): self
    {
        return new self(false, $message);
    }
}

