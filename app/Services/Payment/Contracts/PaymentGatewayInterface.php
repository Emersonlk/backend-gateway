<?php

namespace App\Services\Payment\Contracts;

use App\Services\Payment\DTOs\GatewayChargeResult;
use App\Services\Payment\DTOs\GatewayRefundResult;

interface PaymentGatewayInterface
{
    /**
     * Realiza a cobrança no gateway.
     *
     * @param  int    $amount        Valor em centavos.
     * @param  string $name          Nome do comprador.
     * @param  string $email         Email do comprador.
     * @param  string $cardNumber    Número completo do cartão (16 dígitos).
     * @param  string $cvv           Código de segurança.
     */
    public function charge(
        int $amount,
        string $name,
        string $email,
        string $cardNumber,
        string $cvv,
    ): GatewayChargeResult;

    /**
     * Realiza o reembolso no gateway.
     *
     * @param  string  $externalId  ID/identificador externo da transação no gateway.
     */
    public function refund(string $externalId): GatewayRefundResult;
}

