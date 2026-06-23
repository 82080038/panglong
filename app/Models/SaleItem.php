<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'bonus_qty',
        'unit_id',
        'unit_price',
        'discount',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'bonus_qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the sale for the item.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the product for the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit for the item.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
