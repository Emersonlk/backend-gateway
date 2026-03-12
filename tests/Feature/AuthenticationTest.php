<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_login_com_credenciais_validas_retorna_token(): void
    {
        $this->seed(UserSeeder::class);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role'],
            ]);
    }

    public function test_login_com_credenciais_invalidas_retorna_erro_de_validacao(): void
    {
        $this->seed(UserSeeder::class);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'senha_errada',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_rota_protegida_sem_token_retorna_401(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(401);
    }
}
