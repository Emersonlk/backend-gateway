<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::query()->create([
            'name' => 'Plano Básico',
            'amount' => 1000,
        ]);

        Product::query()->create([
            'name' => 'Plano Intermediário',
            'amount' => 2500,
        ]);

        Product::query()->create([
            'name' => 'Plano Avançado',
            'amount' => 5000,
        ]);
    }
}
