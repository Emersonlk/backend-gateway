<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class UserProductAuthorizationTest extends TestCase
{
    private function loginAs(string $email): string
    {
        $this->seed(DatabaseSeeder::class);

        $login = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'password',
        ]);

        return $login->json('token');
    }

    public function test_manager_pode_criar_usuario(): void
    {
        $token = $this->loginAs('manager@example.com');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users', [
                'name' => 'Novo User',
                'email' => 'novo.user+manager@example.com',
                'password' => 'password',
                'role' => 'USER',
            ]);

        $response->assertStatus(201);
    }

    public function test_finance_nao_pode_criar_usuario(): void
    {
        $token = $this->loginAs('finance@example.com');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users', [
                'name' => 'Novo User',
                'email' => 'novo.user+finance@example.com',
                'password' => 'password',
                'role' => 'USER',
            ]);

        $response->assertStatus(403);
    }

    public function test_finance_pode_criar_produto(): void
    {
        $token = $this->loginAs('finance@example.com');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/products', [
                'name' => 'Produto Finance',
                'amount' => 1234,
            ]);

        $response->assertStatus(201);
    }

    public function test_user_nao_pode_criar_produto(): void
    {
        $token = $this->loginAs('user@example.com');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/products', [
                'name' => 'Produto User',
                'amount' => 1234,
            ]);

        $response->assertStatus(403);
    }
}
