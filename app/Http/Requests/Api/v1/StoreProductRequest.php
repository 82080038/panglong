<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:products,code',
            'name' => 'required|string|max:255',
            'alias' => 'nullable|array',
            'category_id' => 'nullable|exists:categories,id',
            'brand' => 'nullable|string|max:100',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:50',
            'buy_price' => 'nullable|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'units' => 'required|array|min:1',
            'units.*.unit_name' => 'required|string|max:20',
            'units.*.conversion_factor' => 'required|numeric|min:0.001',
            'units.*.is_base_unit' => 'nullable|boolean',
            'units.*.price_per_unit' => 'nullable|numeric|min:0',
            'barcodes' => 'nullable|array',
            'barcodes.*.barcode' => 'required|string|max:50',
            'barcodes.*.unit_id' => 'nullable|exists:product_units,id',
            'barcodes.*.is_primary' => 'nullable|boolean',
        ];
    }
}
