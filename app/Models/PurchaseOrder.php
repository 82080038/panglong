<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class PurchaseOrder extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'po_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'freight_cost',
        'insurance_cost',
        'handling_cost',
        'landed_total',
        'payment_status',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'po_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'freight_cost' => 'decimal:2',
        'insurance_cost' => 'decimal:2',
        'handling_cost' => 'decimal:2',
        'landed_total' => 'decimal:2',
    ];

    /**
     * Get the supplier for the purchase order.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created the purchase order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the items for the purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get the payments for the purchase order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    /**
     * Get the accounts payable for the purchase order.
     */
    public function accountsPayable()
    {
        return $this->hasOne(AccountPayable::class);
    }
}
