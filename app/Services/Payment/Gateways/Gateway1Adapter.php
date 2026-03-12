<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\GatewayChargeResult;
use App\Services\Payment\DTOs\GatewayRefundResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Gateway1Adapter implements PaymentGatewayInterface
{
    private string $baseUrl;
    private string $email;
    private string $token;
    private int $cacheTtl;

    public function __construct()
    {
        $config = config('gateways.gateway1');

        $this->baseUrl = rtrim($config['base_url'] ?? '', '/');
        $this->email = (string) ($config['email'] ?? '');
        $this->token = (string) ($config['token'] ?? '');
        $this->cacheTtl = (int) ($config['cache_ttl'] ?? 3600);
    }

    public function charge(
        int $amount,
        string $name,
        string $email,
        string $cardNumber,
        string $cvv,
    ): GatewayChargeResult {
        $bearer = $this->getAccessToken();

        if ($bearer === null) {
            return GatewayChargeResult::failure('Não foi possível autenticar no Gateway 1.');
        }

        try {
            $response = Http::withToken($bearer)
                ->post($this->baseUrl . '/transactions', [
                    'amount' => $amount,
                    'name' => $name,
                    'email' => $email,
                    'cardNumber' => $cardNumber,
                    'cvv' => $cvv,
                ]);
        } catch (\Exception $e) {
            return GatewayChargeResult::failure('Erro de comunicação com Gateway 1.');
        }

        if (! $response->successful()) {
            return GatewayChargeResult::failure('Cobrança recusada pelo Gateway 1.');
        }

        $data = $response->json();
        $externalId = $data['id'] ?? $data['external_id'] ?? null;

        if (! is_string($externalId) || $externalId === '') {
            return GatewayChargeResult::failure('Gateway 1 não retornou identificador da transação.');
        }

        return GatewayChargeResult::success($externalId);
    }

    public function refund(string $externalId): GatewayRefundResult
    {
        $bearer = $this->getAccessToken();

        if ($bearer === null) {
            return GatewayRefundResult::failure('Não foi possível autenticar no Gateway 1.');
        }

        $response = $this->sendRefundRequest($bearer, $externalId);

        // Se o token JWT expirou, limpa o cache, obtém novo token e tenta novamente uma vez
        if ($response && $response->status() === 401 && str_contains($response->body(), 'jwt expired')) {
            $cacheKey = 'gateway1_token_' . md5($this->baseUrl . '|' . $this->email);
            Cache::forget($cacheKey);

            $bearer = $this->getAccessToken();
            if ($bearer === null) {
                return GatewayRefundResult::failure('Não foi possível autenticar no Gateway 1 (token expirado).');
            }

            $response = $this->sendRefundRequest($bearer, $externalId);
        }

        if (! $response || ! $response->successful()) {
            if ($response) {
                Log::warning('Gateway1 refund failed', [
                    'external_id' => $externalId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return GatewayRefundResult::failure('Reembolso recusado pelo Gateway 1.');
        }

        return GatewayRefundResult::success();
    }

    private function getAccessToken(): ?string
    {
        if ($this->baseUrl === '' || $this->email === '' || $this->token === '') {
            return null;
        }

        $cacheKey = 'gateway1_token_' . md5($this->baseUrl . '|' . $this->email);

        /** @var string|null $cached */
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $response = Http::post($this->baseUrl . '/login', [
                'email' => $this->email,
                'token' => $this->token,
            ]);
        } catch (\Exception $e) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $bearer = $data['token'] ?? $data['access_token'] ?? null;

        if (! is_string($bearer) || $bearer === '') {
            return null;
        }

        Cache::put($cacheKey, $bearer, $this->cacheTtl);

        return $bearer;
    }

    private function sendRefundRequest(string $bearer, string $externalId)
    {
        try {
            return Http::withToken($bearer)
                ->post($this->baseUrl . '/transactions/' . urlencode($externalId) . '/charge_back');
        } catch (\Exception $e) {
            Log::warning('Gateway1 refund exception', [
                'external_id' => $externalId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

