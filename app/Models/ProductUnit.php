<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_name',
        'conversion_factor',
        'is_base_unit',
        'price_per_unit',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:3',
        'is_base_unit' => 'boolean',
        'price_per_unit' => 'decimal:2',
    ];

    /**
     * Get the product that owns the unit.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
