<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandForecast extends Model
{
    protected $fillable = ['product_id', 'tenant_id', 'forecast_date', 'horizon_days',
        'predicted_demand', 'confidence_lower', 'confidence_upper', 'confidence_score',
        'method', 'factors'];

    protected $casts = ['factors' => 'array'];

    public function product() { return $this->belongsTo(Product::class); }
}
