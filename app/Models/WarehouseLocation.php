<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id', 'code', 'name', 'zone_type',
        'aisle', 'level', 'max_weight_kg', 'capacity_m2', 'is_active',
    ];

    protected $casts = [
        'max_weight_kg' => 'decimal:2',
        'capacity_m2' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
