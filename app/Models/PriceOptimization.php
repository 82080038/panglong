<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceOptimization extends Model
{
    protected $fillable = ['product_id', 'tenant_id', 'current_price', 'suggested_price',
        'current_margin', 'suggested_margin', 'estimated_demand_change',
        'estimated_revenue_change', 'reasoning', 'generated_date'];

    public function product() { return $this->belongsTo(Product::class); }
}
