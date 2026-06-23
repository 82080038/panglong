<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_no', 'nik', 'full_name', 'phone', 'email', 'address',
        'position', 'branch_id', 'warehouse_id', 'user_id',
        'base_salary', 'commission_pct', 'hire_date', 'resign_date',
        'status', 'vehicle_plate', 'sim_no',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'commission_pct' => 'decimal:2',
        'hire_date' => 'date',
        'resign_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function managedWarehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'manager_employee_id');
    }
}
