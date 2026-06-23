<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = ['name', 'code', 'description', 'price_monthly', 'price_yearly',
        'max_users', 'max_products', 'max_warehouses', 'has_accounting', 'has_multi_warehouse',
        'has_api_access', 'has_custom_branding', 'is_active'];

    protected $casts = [
        'has_accounting' => 'boolean',
        'has_multi_warehouse' => 'boolean',
        'has_api_access' => 'boolean',
        'has_custom_branding' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
