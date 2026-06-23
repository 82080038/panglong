<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = ['code', 'name', 'subdomain', 'logo_url', 'primary_color', 'secondary_color',
        'company_name', 'company_address', 'company_phone', 'company_email', 'tax_id',
        'status', 'trial_ends_at', 'subscription_ends_at'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }
}
