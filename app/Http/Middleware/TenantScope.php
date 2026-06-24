<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantScope
{
    public function handle(Request $request, Closure $next)
    {
        // Resolve the tenant from the authenticated token's user and bind it
        // for the lifetime of this request. This avoids relying on the session
        // store, which is not started for stateless (token) API requests.
        if ($request->user() && $request->user()->tenant_id) {
            app()->instance('currentTenantId', $request->user()->tenant_id);
        }
        return $next($request);
    }
}
