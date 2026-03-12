<?php

namespace App\Services\Payment;

use App\Enums\TransactionStatus;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\GatewayChargeResult;
use App\Services\Payment\Gateways\Gateway1Adapter;
use App\Services\Payment\Gateways\Gateway2Adapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentOrchestrator
{
    public function __construct(
        private readonly Gateway1Adapter $gateway1,
        private readonly Gateway2Adapter $gateway2,
    ) {
    }

    /**
     * Tenta realizar a cobrança utilizando os gateways ativos por ordem de prioridade.
     *
     * @param  Client                $client
     * @param  Collection<int, Product> $products
     * @param  array<int, int>       $quantities   Mapa [product_id => quantity]
     * @param  string                $cardNumber
     * @param  string                $cvv
     */
    public function charge(
        Client $client,
        Collection $products,
        array $quantities,
        string $cardNumber,
        string $cvv,
    ): Transaction {
        $amount = $this->calculateAmount($products, $quantities);
        $cardLastNumbers = substr(preg_replace('/\D/', '', $cardNumber), -4);

        return DB::transaction(function () use ($client, $products, $quantities, $amount, $cardNumber, $cvv, $cardLastNumbers) {
            /** @var Transaction $transaction */
            $transaction = Transaction::query()->create([
                'client_id' => $client->id,
                'gateway_id' => null,
                'external_id' => null,
                'status' => TransactionStatus::PENDING,
                'amount' => $amount,
                'card_last_numbers' => $cardLastNumbers,
            ]);

            foreach ($products as $product) {
                $quantity = $quantities[$product->id] ?? 0;

                if ($quantity <= 0) {
                    continue;
                }

                $transaction->products()->attach($product->id, [
                    'quantity' => $quantity,
                ]);
            }

            $gateways = Gateway::query()
                ->where('is_active', true)
                ->orderBy('priority')
                ->get();

            $chargeResult = null;
            $chosenGateway = null;

            foreach ($gateways as $gateway) {
                $adapter = $this->resolveAdapterForGateway($gateway);

                if (! $adapter instanceof PaymentGatewayInterface) {
                    continue;
                }

                $chargeResult = $adapter->charge(
                    $amount,
                    $client->name,
                    $client->email,
                    $cardNumber,
                    $cvv,
                );

                if ($chargeResult->success) {
                    $chosenGateway = $gateway;
                    break;
                }
            }

            if ($chargeResult instanceof GatewayChargeResult && $chargeResult->success && $chosenGateway instanceof Gateway) {
                $transaction->update([
                    'gateway_id' => $chosenGateway->id,
                    'external_id' => $chargeResult->externalId,
                    'status' => TransactionStatus::PAID,
                ]);
            } else {
                $transaction->update([
                    'status' => TransactionStatus::FAILED,
                ]);
            }

            return $transaction->fresh(['client', 'gateway', 'products']);
        });
    }

    /**
     * Realiza o reembolso utilizando o adapter correspondente ao gateway da transação.
     */
    public function refund(Transaction $transaction): bool
    {
        if (! $transaction->gateway || ! $transaction->external_id) {
            return false;
        }

        $adapter = $this->resolveAdapterForGateway($transaction->gateway);

        if (! $adapter instanceof PaymentGatewayInterface) {
            return false;
        }

        $result = $adapter->refund($transaction->external_id);

        if (! $result->success) {
            return false;
        }

        $transaction->update([
            'status' => TransactionStatus::REFUNDED,
        ]);

        return true;
    }

    private function calculateAmount(Collection $products, array $quantities): int
    {
        return (int) $products->reduce(
            function (int $carry, Product $product) use ($quantities) {
                $quantity = $quantities[$product->id] ?? 0;

                if ($quantity <= 0) {
                    return $carry;
                }

                return $carry + ($product->amount * $quantity);
            },
            0
        );
    }

    private function resolveAdapterForGateway(Gateway $gateway): ?PaymentGatewayInterface
    {
        $key = $gateway->code ?: $gateway->name;

        return match ($key) {
            'gateway1', 'Gateway 1' => $this->gateway1,
            'gateway2', 'Gateway 2' => $this->gateway2,
            default => null,
        };
    }
}

