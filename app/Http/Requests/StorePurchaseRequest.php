<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client.name' => ['required', 'string', 'max:255'],
            'client.email' => ['required', 'string', 'email', 'max:255'],

            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],

            'card.number' => ['required', 'digits:16'],
            'card.cvv' => ['required', 'digits:3'],
        ];
    }
}
