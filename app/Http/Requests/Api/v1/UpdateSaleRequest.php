<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'items' => 'sometimes|array',
            'discount' => 'sometimes|numeric|min:0',
            'payment_method' => 'sometimes|in:cash,credit,transfer',
            'notes' => 'sometimes|string|max:500',
        ];
    }
}
