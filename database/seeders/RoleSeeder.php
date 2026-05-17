<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Owner', 'slug' => 'owner', 'description' => 'Full access to all features'],
            ['name' => 'Manager', 'slug' => 'manager', 'description' => 'Manager level access'],
            ['name' => 'Kasir', 'slug' => 'kasir', 'description' => 'Cashier access'],
            ['name' => 'Gudang', 'slug' => 'gudang', 'description' => 'Warehouse access'],
            ['name' => 'Accounting', 'slug' => 'accounting', 'description' => 'Accounting access'],
            ['name' => 'Supervisor', 'slug' => 'supervisor', 'description' => 'Supervisor access'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
