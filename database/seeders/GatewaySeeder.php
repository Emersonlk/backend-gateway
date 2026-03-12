<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Gateway::query()->create([
            'code' => 'gateway1',
            'name' => 'Gateway 1',
            'is_active' => true,
            'priority' => 1,
        ]);

        Gateway::query()->create([
            'code' => 'gateway2',
            'name' => 'Gateway 2',
            'is_active' => true,
            'priority' => 2,
        ]);
    }
}
