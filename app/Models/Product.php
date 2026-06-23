<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class Product extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'code',
        'name',
        'alias',
        'category_id',
        'brand',
        'min_stock',
        'max_stock',
        'location',
        'buy_price',
        'sell_price',
        'is_active',
    ];

    protected $casts = [
        'alias' => 'array',
        'min_stock' => 'decimal:3',
        'max_stock' => 'decimal:3',
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category that the product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the base unit for the product (via is_base_unit flag).
     */
    public function baseUnit()
    {
        return $this->hasOne(ProductUnit::class)->where('is_base_unit', true);
    }

    /**
     * Get the units for the product.
     */
    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    /**
     * Get the barcodes for the product.
     */
    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class);
    }

    /**
     * Get the stock movements for the product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the sale items for the product.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the purchase items for the product.
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get the stock adjustments for the product.
     */
    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    /**
     * Get the opname items for the product.
     */
    public function opnameItems(): HasMany
    {
        return $this->hasMany(OpnameItem::class);
    }

    /**
     * Scope to filter active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted price attribute.
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->sell_price, 0, ',', '.');
    }

    /**
     * Get current stock.
     */
    public function getCurrentStockAttribute(): float
    {
        return $this->stockMovements()->sum('quantity');
    }
}
