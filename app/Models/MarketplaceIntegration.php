<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceIntegration extends Model
{
    protected $fillable = ['tenant_id', 'platform', 'shop_id', 'shop_name',
        'access_token', 'refresh_token', 'token_expires_at', 'status', 'last_synced_at'];

    public function mappings() { return $this->hasMany(MarketplaceProductMapping::class, 'integration_id'); }
}
