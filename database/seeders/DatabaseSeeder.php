<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            SubscriptionPlansSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            CustomerGroupSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            ChartOfAccountsSeeder::class,
            WarehouseSeeder::class,
            OrganizationSeeder::class,
            AppSettingSeeder::class,
            StockSeeder::class,
        ]);
    }
}
