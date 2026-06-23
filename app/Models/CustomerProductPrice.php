<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'product_id', 'unit_id', 'custom_price',
        'min_qty', 'is_active', 'notes',
    ];

    protected $casts = [
        'custom_price' => 'decimal:2',
        'min_qty' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
