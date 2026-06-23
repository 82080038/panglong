<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class CustomerGroup extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'discount_pct',
        'credit_limit',
        'is_active',
    ];

    protected $casts = [
        'discount_pct' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * The customers that belong to the group.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
