<?php

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'group' => $this->whenLoaded('group', fn() => [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'discount_pct' => $this->group->discount_pct,
            ]),
            'credit_limit' => $this->credit_limit,
            'payment_terms' => $this->payment_terms,
            'credit_score' => $this->credit_score,
            'is_active' => $this->is_active,
            'outstanding_balance' => $this->when(isset($this->outstanding_balance), fn() => $this->outstanding_balance),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
