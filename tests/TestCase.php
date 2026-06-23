<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    protected function actingAsUser(string $role = 'kasir')
    {
        $roleModel = \App\Models\Role::where('slug', $role)->first();

        $user = \App\Models\User::factory()->create([
            'role_id' => $roleModel->id,
        ]);

        return $this->actingAs($user, 'sanctum');
    }
}
