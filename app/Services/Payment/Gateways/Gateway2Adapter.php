<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\GatewayChargeResult;
use App\Services\Payment\DTOs\GatewayRefundResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class Gateway2Adapter implements PaymentGatewayInterface
{
    private string $baseUrl;
    private string $authToken;
    private string $authSecret;

    public function __construct()
    {
        $config = config('gateways.gateway2');

        $this->baseUrl = rtrim($config['base_url'] ?? '', '/');
        $this->authToken = (string) ($config['auth_token'] ?? '');
        $this->authSecret = (string) ($config['auth_secret'] ?? '');
    }

    public function charge(
        int $amount,
        string $name,
        string $email,
        string $cardNumber,
        string $cvv,
    ): GatewayChargeResult {
        if ($this->baseUrl === '' || $this->authToken === '' || $this->authSecret === '') {
            return GatewayChargeResult::failure('Configuração do Gateway 2 inválida.');
        }

        try {
            $response = Http::withHeaders([
                'Gateway-Auth-Token' => $this->authToken,
                'Gateway-Auth-Secret' => $this->authSecret,
            ])->post($this->baseUrl . '/transacoes', [
                'valor' => $amount,
                'nome' => $name,
                'email' => $email,
                'numeroCartao' => $cardNumber,
                'cvv' => $cvv,
            ]);
        } catch (Exception $e) {
            return GatewayChargeResult::failure('Erro de comunicação com Gateway 2.');
        }

        if (! $response->successful()) {
            return GatewayChargeResult::failure('Cobrança recusada pelo Gateway 2.');
        }

        $data = $response->json();
        $externalId = $data['id'] ?? $data['external_id'] ?? null;

        if (! is_string($externalId) || $externalId === '') {
            return GatewayChargeResult::failure('Gateway 2 não retornou identificador da transação.');
        }

        return GatewayChargeResult::success($externalId);
    }

    public function refund(string $externalId): GatewayRefundResult
    {
        if ($this->baseUrl === '' || $this->authToken === '' || $this->authSecret === '') {
            return GatewayRefundResult::failure('Configuração do Gateway 2 inválida.');
        }

        try {
            $response = Http::withHeaders([
                'Gateway-Auth-Token' => $this->authToken,
                'Gateway-Auth-Secret' => $this->authSecret,
            ])->post($this->baseUrl . '/transacoes/reembolso', [
                'id' => $externalId,
            ]);
        } catch (Throwable $e) {
            Log::warning('Gateway2 refund exception', [
                'external_id' => $externalId,
                'message' => $e->getMessage(),
            ]);

            return GatewayRefundResult::failure('Erro de comunicação com Gateway 2.');
        }

        if (! $response->successful()) {
            Log::warning('Gateway2 refund failed', [
                'external_id' => $externalId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return GatewayRefundResult::failure('Reembolso recusado pelo Gateway 2.');
        }

        return GatewayRefundResult::success();
    }
}

