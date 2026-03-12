<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Http;
use App\Models\Product;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    public function test_validacao_falha_quando_nao_envia_produtos(): void
    {
        $payload = [
            'client' => [
                'name' => 'Cliente Teste',
                'email' => 'cliente@example.com',
            ],
            'products' => [],
            'card' => [
                'number' => '5569000000006063',
                'cvv' => '010',
            ],
        ];

        $response = $this->postJson('/api/purchase', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('products');
    }

    public function test_compra_com_multiplos_produtos_utiliza_gateway1_com_sucesso(): void
    {
        $this->seed(DatabaseSeeder::class);

        $products = Product::query()->take(2)->get();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if (str_contains($url, '/login')) {
                return Http::response(['token' => 'jwt-token'], 200);
            }

            if (str_contains($url, '/transactions')) {
                return Http::response(['id' => 'gw1-transaction-id'], 200);
            }

            if (str_contains($url, '/transacoes')) {
                return Http::response([], 500);
            }

            return Http::response([], 500);
        });

        $payload = [
            'client' => [
                'name' => 'Cliente Teste',
                'email' => 'cliente@example.com',
            ],
            'products' => [
                ['product_id' => $products[0]->id, 'quantity' => 1],
                ['product_id' => $products[1]->id, 'quantity' => 2],
            ],
            'card' => [
                'number' => '5569000000006063',
                'cvv' => '010',
            ],
        ];

        $response = $this->postJson('/api/purchase', $payload);

        $response
            ->assertStatus(201)
            ->assertJsonPath('status', 'paid')
            ->assertJsonStructure([
                'id',
                'status',
                'amount',
                'gateway',
                'external_id',
            ]);
    }

    public function test_quando_gateway1_falha_compra_e_processada_no_gateway2(): void
    {
        $this->seed(DatabaseSeeder::class);

        $products = Product::query()->take(2)->get();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if (str_contains($url, '/login')) {
                return Http::response(['token' => 'jwt-token'], 200);
            }

            if (str_contains($url, '/transactions')) {
                return Http::response(['error' => 'fail'], 500);
            }

            if (str_contains($url, '/transacoes')) {
                return Http::response(['id' => 'gw2-transaction-id'], 200);
            }

            return Http::response([], 500);
        });

        $payload = [
            'client' => [
                'name' => 'Cliente Teste',
                'email' => 'cliente+gw2@example.com',
            ],
            'products' => [
                ['product_id' => $products[0]->id, 'quantity' => 1],
                ['product_id' => $products[1]->id, 'quantity' => 1],
            ],
            'card' => [
                'number' => '5569000000006063',
                'cvv' => '010',
            ],
        ];

        $response = $this->postJson('/api/purchase', $payload);

        $response
            ->assertStatus(201)
            ->assertJsonPath('status', 'paid')
            ->assertJsonStructure([
                'id',
                'status',
                'amount',
                'gateway',
                'external_id',
            ]);
    }

}
