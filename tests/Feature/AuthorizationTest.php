<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use App\Enums\TransactionStatus;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Transaction;
use Tests\TestCase;
class AuthorizationTest extends TestCase
{
    public function test_user_nao_pode_realizar_reembolso(): void
    {
        $this->seed([
            \Database\Seeders\DatabaseSeeder::class,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Teste',
            'email' => 'cliente+auth@example.com',
        ]);

        $gateway = Gateway::query()->firstOrFail();

        $transaction = Transaction::query()->create([
            'client_id' => $client->id,
            'gateway_id' => $gateway->id,
            'external_id' => 'dummy-external-id',
            'status' => TransactionStatus::PAID,
            'amount' => 1000,
            'card_last_numbers' => '6063',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $token = $login->json('token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/transactions/'.$transaction->id.'/refund');

        $response
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Você não tem permissão para executar esta ação.',
            ]);
    }
}
