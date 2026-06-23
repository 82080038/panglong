<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;

class SubscriptionPlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'code' => 'STARTER',
                'description' => 'Untuk toko kecil, 1 user, 100 produk',
                'price_monthly' => 99000,
                'price_yearly' => 990000,
                'max_users' => 1,
                'max_products' => 100,
                'max_warehouses' => 1,
                'has_accounting' => false,
                'has_multi_warehouse' => false,
                'has_api_access' => true,
                'has_custom_branding' => false,
            ],
            [
                'name' => 'Business',
                'code' => 'BUSINESS',
                'description' => 'Untuk toko menengah, 5 user, 1000 produk, accounting',
                'price_monthly' => 299000,
                'price_yearly' => 2990000,
                'max_users' => 5,
                'max_products' => 1000,
                'max_warehouses' => 2,
                'has_accounting' => true,
                'has_multi_warehouse' => true,
                'has_api_access' => true,
                'has_custom_branding' => false,
            ],
            [
                'name' => 'Enterprise',
                'code' => 'ENTERPRISE',
                'description' => 'Untuk distributor besar, unlimited user, multi-warehouse, white label',
                'price_monthly' => 999000,
                'price_yearly' => 9990000,
                'max_users' => 100,
                'max_products' => 100000,
                'max_warehouses' => 50,
                'has_accounting' => true,
                'has_multi_warehouse' => true,
                'has_api_access' => true,
                'has_custom_branding' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create(array_merge($plan, ['is_active' => true]));
        }

        // Create default tenant
        $tenant = Tenant::create([
            'code' => 'TEN-DEFAULT',
            'name' => 'Panglong Default',
            'subdomain' => 'default',
            'company_name' => 'Panglong Material Bangunan',
            'company_address' => 'Jl. Raya Panglong No. 1',
            'company_phone' => '021-1234567',
            'company_email' => 'info@panglong.com',
            'status' => 'active',
            'subscription_ends_at' => now()->addYear(),
        ]);

        // Assign all existing users to default tenant
        \App\Models\User::whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
    }
}
