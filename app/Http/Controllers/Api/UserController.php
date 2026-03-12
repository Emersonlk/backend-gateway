<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('manageUsers');

        $users = User::query()->get();

        return response()->json($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('manageUsers');

        $user = User::query()->create($request->validated());

        return response()->json($user, 201);
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('manageUsers');

        return response()->json($user);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('manageUsers');

        $user->update($request->validated());

        return response()->json($user);
    }

    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('manageUsers');

        $user->delete();

        return response()->json([], 204);
    }
}
