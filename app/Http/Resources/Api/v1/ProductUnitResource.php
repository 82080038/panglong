<?php

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductUnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unit_name' => $this->unit_name,
            'conversion_factor' => $this->conversion_factor,
            'is_base_unit' => $this->is_base_unit,
            'price_per_unit' => $this->price_per_unit,
        ];
    }
}
