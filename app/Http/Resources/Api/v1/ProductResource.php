<?php

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'alias' => $this->alias,
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'brand' => $this->brand,
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock,
            'location' => $this->location,
            'buy_price' => $this->buy_price,
            'sell_price' => $this->sell_price,
            'formatted_price' => $this->formatted_price,
            'is_active' => $this->is_active,
            'current_stock' => $this->whenLoaded('stockMovements', fn() => $this->current_stock),
            'units' => $this->whenLoaded('units', fn() => ProductUnitResource::collection($this->units)),
            'base_unit' => $this->whenLoaded('baseUnit', fn() => new ProductUnitResource($this->baseUnit)),
            'barcodes' => $this->whenLoaded('barcodes', fn() => $this->barcodes->pluck('barcode')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
