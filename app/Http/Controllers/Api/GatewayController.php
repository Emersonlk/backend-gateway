<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GatewayController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('manageGateways');

        $gateways = Gateway::query()->orderBy('priority')->get();

        return response()->json($gateways);
    }

    public function toggleActive(Gateway $gateway): JsonResponse
    {
        Gate::authorize('manageGateways');

        $gateway->update([
            'is_active' => ! $gateway->is_active,
        ]);

        return response()->json($gateway);
    }

    public function updatePriority(Request $request, Gateway $gateway): JsonResponse
    {
        Gate::authorize('manageGateways');

        $validated = $request->validate([
            'priority' => ['required', 'integer', 'min:1'],
        ]);

        $gateway->update([
            'priority' => $validated['priority'],
        ]);

        return response()->json($gateway);
    }
}
