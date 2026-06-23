<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::all();
        $owner = Role::where('slug', 'owner')->first();
        $manager = Role::where('slug', 'manager')->first();
        $kasir = Role::where('slug', 'kasir')->first();
        $gudang = Role::where('slug', 'gudang')->first();
        $accounting = Role::where('slug', 'accounting')->first();
        $supervisor = Role::where('slug', 'supervisor')->first();

        // Owner: all permissions
        if ($owner) {
            $owner->permissions()->sync($allPermissions->pluck('id'));
        }

        // Manager: all except manage_users
        if ($manager) {
            $managerPerms = $allPermissions->where('name', '!=', 'manage_users');
            $manager->permissions()->sync($managerPerms->pluck('id'));
        }

        // Kasir: create_sales, edit_sales, manage_customers, record_payment
        if ($kasir) {
            $kasirPerms = $allPermissions->whereIn('name', ['create_sales', 'edit_sales', 'manage_customers', 'record_payment']);
            $kasir->permissions()->sync($kasirPerms->pluck('id'));
        }

        // Gudang: manage_products, stock_adjustment, manage_suppliers
        if ($gudang) {
            $gudangPerms = $allPermissions->whereIn('name', ['manage_products', 'stock_adjustment', 'manage_suppliers']);
            $gudang->permissions()->sync($gudangPerms->pluck('id'));
        }

        // Accounting: view_reports, record_payment, manage_customers
        if ($accounting) {
            $accountingPerms = $allPermissions->whereIn('name', ['view_reports', 'record_payment', 'manage_customers']);
            $accounting->permissions()->sync($accountingPerms->pluck('id'));
        }

        // Supervisor: view_reports, view_profit
        if ($supervisor) {
            $supervisorPerms = $allPermissions->whereIn('name', ['view_reports', 'view_profit']);
            $supervisor->permissions()->sync($supervisorPerms->pluck('id'));
        }
    }
}
