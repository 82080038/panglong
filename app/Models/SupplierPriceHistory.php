<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id', 'product_id', 'unit_id', 'unit_price',
        'effective_date', 'end_date', 'po_reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
