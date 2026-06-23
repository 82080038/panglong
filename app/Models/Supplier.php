<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class Supplier extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'payment_terms',
        'credit_limit',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the purchase orders for the supplier.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get the accounts payable for the supplier.
     */
    public function accountsPayable(): HasMany
    {
        return $this->hasMany(AccountPayable::class);
    }
}
