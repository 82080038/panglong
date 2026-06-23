<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTierPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'unit_id', 'min_qty', 'max_qty', 'unit_price', 'is_active',
    ];

    protected $casts = [
        'min_qty' => 'decimal:3',
        'max_qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
