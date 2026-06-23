<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDepreciation extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixed_asset_id', 'depreciation_date', 'amount',
        'accumulated_after', 'book_value_after',
        'journal_entry_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'depreciation_date' => 'date',
        'amount' => 'decimal:2',
        'accumulated_after' => 'decimal:2',
        'book_value_after' => 'decimal:2',
    ];

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
