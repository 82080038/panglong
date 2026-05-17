<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'create_sales',
            'edit_sales',
            'void_sales',
            'view_profit',
            'manage_products',
            'stock_adjustment',
            'approve_adjustment',
            'manage_customers',
            'manage_suppliers',
            'record_payment',
            'view_reports',
            'manage_users',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'description' => str_replace('_', ' ', ucfirst($permission)),
            ]);
        }
    }
}
