<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class Customer extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'group_id',
        'credit_limit',
        'payment_terms',
        'credit_score',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the group that the customer belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    /**
     * Get the sales for the customer.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the accounts receivable for the customer.
     */
    public function accountsReceivable(): HasMany
    {
        return $this->hasMany(AccountReceivable::class);
    }
}
