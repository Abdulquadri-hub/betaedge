<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionLimit
{
    public function handle(Request $request, Closure $next, string $limitType)
    {
        $tenantId = session('active_tenant_id');
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            abort(404, 'School not found');
        }

        if ($tenant->hasReachedLimit($limitType)) {
            return response()->json([
                'error' => "You have reached your {$limitType} limit. Please upgrade your plan.",
                'upgrade_url' => route('tenant.subscription.upgrade'),
            ], 403);
        }

        return $next($request);
    }
}
