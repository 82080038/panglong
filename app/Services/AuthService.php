<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * Login user
     */
    public function login(string $username, string $password): array
    {
        $user = User::where('username', $username)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'data' => null,
            ];
        }

        if (!$user->is_active) {
            return [
                'success' => false,
                'message' => 'Account is inactive',
                'data' => null,
            ];
        }

        $user->update(['last_login_at' => now()]);
        
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'role' => [
                        'id' => $user->role->id,
                        'name' => $user->role->name,
                        'slug' => $user->role->slug,
                    ],
                    'permissions' => $user->role->permissions->pluck('name')->toArray(),
                ],
            ],
        ];
    }

    /**
     * Logout user
     */
    public function logout(): bool
    {
        Auth::user()->currentAccessToken()->delete();
        return true;
    }

    /**
     * Get current user
     */
    public function me(): array
    {
        $user = Auth::user();
        
        return [
            'id' => $user->id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug,
            ],
            'permissions' => $user->role->permissions->pluck('name')->toArray(),
            'last_login_at' => $user->last_login_at,
        ];
    }
}
