<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roles = [
            UserRole::ADMIN->value,
            UserRole::MANAGER->value,
            UserRole::FINANCE->value,
            UserRole::USER->value,
        ];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:'.implode(',', $roles)],
        ];
    }
}
