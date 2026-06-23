<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AuthService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    public function login(string $username, string $password): array
    {
        $lockKey = "login_lock:{$username}";
        $attemptsKey = "login_attempts:{$username}";

        if (Cache::has($lockKey)) {
            return [
                'success' => false,
                'message' => 'Account locked due to too many failed attempts. Try again in ' . self::LOCK_MINUTES . ' minutes.',
                'data' => null,
            ];
        }

        $user = User::where('username', $username)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $attempts = Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, now()->addMinutes(self::LOCK_MINUTES));

            if ($attempts >= self::MAX_ATTEMPTS) {
                Cache::put($lockKey, true, now()->addMinutes(self::LOCK_MINUTES));
                Cache::forget($attemptsKey);
            }

            return [
                'success' => false,
                'message' => 'Invalid credentials' . ($attempts >= 3 ? " ({$attempts}/" . self::MAX_ATTEMPTS . " attempts)" : ''),
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

        Cache::forget($attemptsKey);
        Cache::forget($lockKey);

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
