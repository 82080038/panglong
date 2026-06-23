<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantScope
{
    public function handle(Request $request, Closure $next)
    {
        // Get tenant from token's user
        if ($request->user() && $request->user()->tenant_id) {
            session(['tenant_id' => $request->user()->tenant_id]);
        }
        return $next($request);
    }
}
