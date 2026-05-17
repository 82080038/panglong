<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpnameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'opname_id',
        'product_id',
        'system_qty',
        'physical_qty',
        'difference',
    ];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'physical_qty' => 'decimal:3',
        'difference' => 'decimal:3',
    ];

    /**
     * Get the stock opname for the item.
     */
    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    /**
     * Get the product for the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
