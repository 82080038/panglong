<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'sales_order_id', 'product_id', 'quantity', 'bonus_qty', 'delivered_qty',
        'unit_id', 'unit_price', 'discount', 'subtotal', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'bonus_qty' => 'decimal:3',
        'delivered_qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
