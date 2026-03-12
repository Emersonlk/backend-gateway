<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\GatewayController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::post('/login', [AuthController::class, 'login']);
Route::post('/purchase', [PurchaseController::class, 'store']);

// Rotas protegidas (auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'role' => $request->user()->role->value,
        ]);
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gateways
    Route::get('/gateways', [GatewayController::class, 'index']);
    Route::post('/gateways/{gateway}/toggle', [GatewayController::class, 'toggleActive']);
    Route::patch('/gateways/{gateway}/priority', [GatewayController::class, 'updatePriority']);

    // Users (ADMIN, MANAGER) - manageUsers
    Route::apiResource('users', UserController::class);

    // Products (ADMIN, MANAGER, FINANCE) - manageProducts
    Route::apiResource('products', ProductController::class);

    // Clients - listar e detalhe com compras
    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);

    // Transactions - listagem, detalhe, reembolso
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund']);
});
