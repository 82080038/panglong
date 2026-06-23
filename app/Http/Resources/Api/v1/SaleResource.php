<?php

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'customer' => $this->whenLoaded('customer', fn() => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
            ]),
            'sale_date' => $this->sale_date,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn() => SaleItemResource::collection($this->items)),
            'payments' => $this->whenLoaded('payments', fn() => SalePaymentResource::collection($this->payments)),
            'created_by' => $this->whenLoaded('creator', fn() => $this->creator->full_name),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
