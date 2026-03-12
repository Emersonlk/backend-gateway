<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('gateway_id')->nullable()->constrained('gateways');
            $table->string('external_id')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('amount');
            $table->string('card_last_numbers', 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
