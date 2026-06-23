<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class AuthApiTest extends TestCase
{
    public function test_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'username',
                        'full_name',
                        'email',
                        'role',
                    ],
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_login_with_nonexistent_user(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'nonexistent',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_me_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_me_endpoint_with_token(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'username',
                    'full_name',
                    'role',
                ],
            ]);

        $this->assertEquals('admin', $response->json('data.username'));
    }

    public function test_logout_with_valid_token(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);
    }

    public function test_protected_routes_require_token(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401);
    }
}
