<?php

namespace Tests\Unit;

use App\Enums\TransactionStatus;
use App\Models\Client;
use App\Models\Product;
use App\Services\Payment\Gateways\Gateway1Adapter;
use App\Services\Payment\Gateways\Gateway2Adapter;
use App\Services\Payment\PaymentOrchestrator;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class PaymentOrchestratorTest extends TestCase
{
    public function test_calcula_amount_a_partir_dos_produtos_e_quantidades(): void
    {
        $this->seed(DatabaseSeeder::class);

        $client = Client::query()->create([
            'name' => 'Cliente Teste',
            'email' => 'cliente+unit@example.com',
        ]);

        $products = Product::query()->take(2)->get();

        app()->bind(Gateway1Adapter::class, function () {
            return new class extends Gateway1Adapter {
                public function __construct()
                {
                    // ignora dependências reais
                }

                public function charge(int $amount, string $name, string $email, string $cardNumber, string $cvv): \App\Services\Payment\DTOs\GatewayChargeResult
                {
                    return \App\Services\Payment\DTOs\GatewayChargeResult::success('gw1-unit-id');
                }

                public function refund(string $externalId): \App\Services\Payment\DTOs\GatewayRefundResult
                {
                    return \App\Services\Payment\DTOs\GatewayRefundResult::success();
                }
            };
        });

        app()->bind(Gateway2Adapter::class, function () {
            return new class extends Gateway2Adapter {
                public function __construct()
                {
                    // ignora dependências reais
                }

                public function charge(int $amount, string $name, string $email, string $cardNumber, string $cvv): \App\Services\Payment\DTOs\GatewayChargeResult
                {
                    return \App\Services\Payment\DTOs\GatewayChargeResult::success('gw2-unit-id');
                }

                public function refund(string $externalId): \App\Services\Payment\DTOs\GatewayRefundResult
                {
                    return \App\Services\Payment\DTOs\GatewayRefundResult::success();
                }
            };
        });

        $orchestrator = app(PaymentOrchestrator::class);

        $transaction = $orchestrator->charge(
            $client,
            $products,
            [
                $products[0]->id => 1,
                $products[1]->id => 2,
            ],
            '5569000000006063',
            '010',
        );

        $expectedAmount = $products[0]->amount * 1 + $products[1]->amount * 2;

        $this->assertSame($expectedAmount, $transaction->amount);
        $this->assertSame(TransactionStatus::PAID, $transaction->status);
    }

    public function test_quando_primeiro_gateway_falha_orquestrador_tenta_segundo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $client = Client::query()->create([
            'name' => 'Cliente Teste',
            'email' => 'cliente+unit-gw2@example.com',
        ]);

        $products = Product::query()->take(1)->get();

        // Gateway 1: falha; Gateway 2: sucesso
        app()->bind(Gateway1Adapter::class, function () {
            return new class extends Gateway1Adapter {
                public function __construct()
                {
                }

                public function charge(int $amount, string $name, string $email, string $cardNumber, string $cvv): \App\Services\Payment\DTOs\GatewayChargeResult
                {
                    return \App\Services\Payment\DTOs\GatewayChargeResult::failure('fail');
                }

                public function refund(string $externalId): \App\Services\Payment\DTOs\GatewayRefundResult
                {
                    return \App\Services\Payment\DTOs\GatewayRefundResult::success();
                }
            };
        });

        app()->bind(Gateway2Adapter::class, function () {
            return new class extends Gateway2Adapter {
                public function __construct()
                {
                }

                public function charge(int $amount, string $name, string $email, string $cardNumber, string $cvv): \App\Services\Payment\DTOs\GatewayChargeResult
                {
                    return \App\Services\Payment\DTOs\GatewayChargeResult::success('gw2-unit-id');
                }

                public function refund(string $externalId): \App\Services\Payment\DTOs\GatewayRefundResult
                {
                    return \App\Services\Payment\DTOs\GatewayRefundResult::success();
                }
            };
        });

        $orchestrator = app(PaymentOrchestrator::class);

        $transaction = $orchestrator->charge(
            $client,
            $products,
            [
                $products[0]->id => 1,
            ],
            '5569000000006063',
            '010',
        );

        $this->assertSame(TransactionStatus::PAID, $transaction->status);
        $this->assertEquals('gw2-unit-id', $transaction->external_id);
        $this->assertEquals('gateway2', $transaction->gateway->code);
    }
}
