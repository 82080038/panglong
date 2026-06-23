<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_code', 'name', 'category', 'branch_id', 'serial_no', 'plate_no',
        'acquisition_date', 'acquisition_cost', 'salvage_value', 'useful_life_months',
        'depreciation_method', 'monthly_depreciation', 'accumulated_depreciation', 'book_value',
        'account_asset_id', 'account_accum_dep_id', 'account_dep_expense_id',
        'status', 'disposal_date', 'disposal_value', 'notes',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'monthly_depreciation' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'disposal_date' => 'date',
        'disposal_value' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_asset_id');
    }

    public function accumDepAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_accum_dep_id');
    }

    public function depExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_dep_expense_id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class);
    }
}
