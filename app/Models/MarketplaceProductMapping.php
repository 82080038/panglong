<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceProductMapping extends Model
{
    protected $fillable = ['integration_id', 'product_id', 'marketplace_item_id',
        'marketplace_url', 'marketplace_price', 'marketplace_stock', 'last_synced_at'];

    public function product() { return $this->belongsTo(Product::class); }
    public function integration() { return $this->belongsTo(MarketplaceIntegration::class, 'integration_id'); }
}
