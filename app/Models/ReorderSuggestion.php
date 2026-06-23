<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReorderSuggestion extends Model
{
    protected $fillable = ['product_id', 'current_stock', 'avg_daily_usage', 'days_of_supply', 'suggested_order_qty', 'priority', 'reason', 'generated_date'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
