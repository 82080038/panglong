<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ownerRole = Role::where('slug', 'owner')->first();
        $managerRole = Role::where('slug', 'manager')->first();
        $kasirRole = Role::where('slug', 'kasir')->first();
        $gudangRole = Role::where('slug', 'gudang')->first();

        $defaultPassword = bcrypt(env('SEED_DEFAULT_PASSWORD', 'password123'));

        $users = [
            [
                'username' => 'admin',
                'password' => $defaultPassword,
                'full_name' => 'Administrator',
                'email' => 'admin@panglong.com',
                'role_id' => $ownerRole->id,
                'is_active' => true,
            ],
            [
                'username' => 'manager1',
                'password' => $defaultPassword,
                'full_name' => 'Manager 1',
                'email' => 'manager1@panglong.com',
                'role_id' => $managerRole->id,
                'is_active' => true,
            ],
            [
                'username' => 'kasir1',
                'password' => $defaultPassword,
                'full_name' => 'Kasir 1',
                'email' => 'kasir1@panglong.com',
                'role_id' => $kasirRole->id,
                'is_active' => true,
            ],
            [
                'username' => 'gudang1',
                'password' => $defaultPassword,
                'full_name' => 'Gudang 1',
                'email' => 'gudang1@panglong.com',
                'role_id' => $gudangRole->id,
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
