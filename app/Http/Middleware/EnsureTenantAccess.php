<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{

    public function handle(Request $request, Closure $next): Response
    {
        if(!Auth::check()) {
            return redirect()->route('login');
        }

        $tenantId = session('active_tenant_id');

        if(!$tenantId) {
            return response()->view('errors.inactive', [
                'tenant_id' => $tenantId,
            ], 403);
        }

        $user = $request->user();

        if(!$user->canAccessTenant($tenantId)) {
            return response()->view('errors.tenant-forbidden', [
                'tenant_id' => $tenantId,
                'message' => 'You do not have access to this school.'
            ], 403);
        }

        return $next($request);
    }
}
