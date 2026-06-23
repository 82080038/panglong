<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'alias' => 'sometimes|array',
            'category_id' => 'sometimes|exists:categories,id',
            'brand' => 'sometimes|string|max:100',
            'min_stock' => 'sometimes|numeric|min:0',
            'max_stock' => 'sometimes|numeric|min:0',
            'location' => 'sometimes|string|max:50',
            'buy_price' => 'sometimes|numeric|min:0',
            'sell_price' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
