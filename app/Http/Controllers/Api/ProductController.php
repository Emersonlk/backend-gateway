<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('manageProducts');

        $products = Product::query()->get();

        return response()->json($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        Gate::authorize('manageProducts');

        $product = Product::query()->create($request->validated());

        return response()->json($product, 201);
    }

    public function show(Product $product): JsonResponse
    {
        Gate::authorize('manageProducts');

        return response()->json($product);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        Gate::authorize('manageProducts');

        $product->update($request->validated());

        return response()->json($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        Gate::authorize('manageProducts');

        $product->delete();

        return response()->json([], 204);
    }
}
