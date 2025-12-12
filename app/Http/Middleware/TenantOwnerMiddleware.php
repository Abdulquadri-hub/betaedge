<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantOwnerMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        if(!Auth::check()) {
            return redirect()->route('login');
        }

        $tenantId = session('active_tenant_id');
        $tenant = Tenant::find($tenantId);

        if(!$tenant) {
            abort(404, 'School not found');
        }

        if(!$request->user()->isOwnerOf($tenant)) {
            abort(403, 'Only school owners can access this');
        }

        return $next($request);
    }
}
