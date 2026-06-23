<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class Delivery extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'delivery_no',
        'sale_id',
        'customer_name',
        'delivery_address',
        'phone',
        'delivery_date',
        'delivery_time',
        'driver_name',
        'vehicle_plate',
        'status',
        'notes',
        'delivered_at',
        'delivery_proof',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'delivered_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
