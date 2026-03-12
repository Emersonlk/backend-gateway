<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Payment\PaymentOrchestrator;
use App\Enums\TransactionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TransactionController extends Controller
{
    public function __construct(
        private readonly PaymentOrchestrator $orchestrator,
    ) {
    }

    public function index(): JsonResponse
    {
        $transactions = Transaction::query()
            ->with(['client', 'gateway', 'products'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($transactions);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load(['client', 'gateway', 'products']);

        return response()->json($transaction);
    }

    public function refund(Transaction $transaction): JsonResponse
    {
        Gate::authorize('performRefund');

        if ($transaction->status === TransactionStatus::REFUNDED) {
            return response()->json([
                'message' => 'Esta transação já foi reembolsada.',
            ], 422);
        }

        if ($transaction->status !== TransactionStatus::PAID) {
            return response()->json([
                'message' => 'Somente transações pagas podem ser reembolsadas.',
            ], 422);
        }

        $transaction->load('gateway');

        if (! $this->orchestrator->refund($transaction)) {
            return response()->json([
                'message' => 'Não foi possível realizar o reembolso.',
            ], 422);
        }

        return response()->json($transaction->refresh());
    }
}
