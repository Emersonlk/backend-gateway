<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('manageProducts');

        $clients = Client::query()->get();

        return response()->json($clients);
    }

    public function show(Client $client): JsonResponse
    {
        Gate::authorize('manageProducts');

        $client->load(['transactions.gateway', 'transactions.products']);

        return response()->json([
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'transactions' => $client->transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'status' => $transaction->status->value,
                    'amount' => $transaction->amount,
                    'gateway' => $transaction->gateway?->name,
                    'external_id' => $transaction->external_id,
                    'products' => $transaction->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'amount' => $product->amount,
                            'quantity' => $product->pivot->quantity,
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }
}
