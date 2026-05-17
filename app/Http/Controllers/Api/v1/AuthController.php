<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $result = $this->authService->login(
            $request->username,
            $request->password
        );

        if (!$result['success']) {
            return response()->json($result, 401);
        }

        return response()->json($result);
    }

    public function logout(Request $request)
    {
        $this->authService->logout();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->authService->me(),
        ]);
    }
}
