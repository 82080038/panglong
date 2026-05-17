<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!Auth::check() || !Auth::user()->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Permission denied',
            ], 403);
        }

        return $next($request);
    }
}
