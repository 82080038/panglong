<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class StockAdjustment extends Model
{
    use HasFactory, BelongsToTenant;

    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'quantity',
        'adjustment_type',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the product for the adjustment.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who approved the adjustment.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created the adjustment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
