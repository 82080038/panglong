<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            RolePermissionSeeder::class,
            SubscriptionPlansSeeder::class,
            UsersTableSeeder::class,
            CategoriesSeeder::class,
            CustomerGroupsSeeder::class,
            SuppliersSeeder::class,
            ChartOfAccountsSeeder::class,
            WarehouseSeeder::class,
            AppSettingSeeder::class,
        ]);
    }
}
