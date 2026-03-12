<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Client;
use App\Models\Product;
use App\Services\Payment\PaymentOrchestrator;
use Illuminate\Http\JsonResponse;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PaymentOrchestrator $orchestrator,
    ) {
    }

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $clientData = $validated['client'];
        $productsPayload = $validated['products'];
        $card = $validated['card'];

        $client = Client::firstOrCreate(
            ['email' => $clientData['email']],
            ['name' => $clientData['name']],
        );

        $quantities = [];
        $productIds = [];

        foreach ($productsPayload as $item) {
            $productIds[] = $item['product_id'];
            $quantities[$item['product_id']] = ($quantities[$item['product_id']] ?? 0) + $item['quantity'];
        }

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get();

        $transaction = $this->orchestrator->charge(
            $client,
            $products,
            $quantities,
            $card['number'],
            $card['cvv'],
        );

        if ($transaction->status->value === 'failed') {
            return response()->json([
                'message' => 'Não foi possível processar o pagamento.',
            ], 422);
        }

        $transaction->load('client', 'gateway', 'products');

        return response()->json([
            'id' => $transaction->id,
            'status' => $transaction->status->value,
            'amount' => $transaction->amount,
            'client' => [
                'id' => $transaction->client->id,
                'name' => $transaction->client->name,
                'email' => $transaction->client->email,
            ],
            'gateway' => $transaction->gateway?->name,
            'external_id' => $transaction->external_id,
            'products' => $transaction->products->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'amount' => $product->amount,
                    'quantity' => $product->pivot->quantity,
                ];
            })->values(),
            'card_last_numbers' => $transaction->card_last_numbers,
        ], 201);
    }
}
