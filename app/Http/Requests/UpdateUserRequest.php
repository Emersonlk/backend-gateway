<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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

        $userId = $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$userId],
            'password' => ['sometimes', 'string', 'min:6'],
            'role' => ['sometimes', 'string', 'in:'.implode(',', $roles)],
        ];
    }
}
