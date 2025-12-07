<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Http\Request;

/**
 * TenantMiddleware - Detects and sets active tenant
 * Based on subdomain or custom domain
 */
class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = $this->detectTenant($request);

        if (!$tenant) {
            // No tenant detected - could be main platform
            if ($this->isMainPlatformRequest($request)) {
                return $next($request);
            }

            abort(404, 'School not found');
        }

        // Check tenant status
        if ($tenant->status !== 'active') {
            return response()->view('errors.tenant-inactive', [
                'tenant' => $tenant
            ], 403);
        }

        // Set active tenant in session
        session(['active_tenant_id' => $tenant->id]);
        
        // Store in request for easy access
        $request->merge(['tenant' => $tenant]);

        // Set database connection if using separate databases per tenant
        // config(['database.connections.tenant.database' => "tenant_{$tenant->id}"]);

        return $next($request);
    }

    protected function detectTenant(Request $request): ?Tenant
    {
        $host = $request->getHost();

        // Check for custom domain first
        $tenant = Tenant::where('custom_domain', $host)
                       ->where('status', 'active')
                       ->first();

        if ($tenant) {
            return $tenant;
        }

        // Check for subdomain
        if ($this->isSubdomain($host)) {
            $subdomain = $this->extractSubdomain($host);
            
            $tenant = Tenant::where('slug', $subdomain)
                           ->where('status', 'active')
                           ->first();

            return $tenant;
        }

        return null;
    }

    protected function isSubdomain(string $host): bool
    {
        $mainDomain = config('app.main_domain', 'teach.com');
        return str_contains($host, $mainDomain) && $host !== $mainDomain;
    }

    protected function extractSubdomain(string $host): string
    {
        $mainDomain = config('app.main_domain', 'teach.com');
        return str_replace('.' . $mainDomain, '', $host);
    }

    protected function isMainPlatformRequest(Request $request): bool
    {
        $host = $request->getHost();
        $mainDomain = config('app.main_domain', 'teach.com');
        
        return $host === $mainDomain || 
               $host === 'www.' . $mainDomain ||
               str_starts_with($request->path(), 'api/');
    }
}


/**
 * EnsureTenantAccess Middleware
 * Verifies user has access to current tenant
 */
class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $tenantId = session('active_tenant_id');

        if (!$tenantId) {
            abort(403, 'No active tenant');
        }

        $user = auth()->user();

        // Check if user has access to this tenant
        if (!$user->canAccessTenant($tenantId)) {
            abort(403, 'You do not have access to this school');
        }

        return $next($request);
    }
}


/**
 * TenantOwnerMiddleware
 * Ensures user is owner of current tenant
 */
class TenantOwnerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $tenantId = session('active_tenant_id');
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            abort(404, 'School not found');
        }

        if (!auth()->user()->isOwnerOf($tenant)) {
            abort(403, 'Only school owners can access this');
        }

        return $next($request);
    }
}


/**
 * CheckSubscriptionLimit Middleware
 * Prevents actions when subscription limits reached
 */
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


/**
 * TenantSubdomainRedirect Middleware
 * Redirects custom domains to subdomain if needed
 */
class TenantSubdomainRedirect
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = $request->get('tenant');

        if (!$tenant) {
            return $next($request);
        }

        // If custom domain exists and verified, redirect to it
        if ($tenant->hasCustomDomain() && $request->getHost() === $tenant->subdomain) {
            $url = $request->url();
            $customUrl = str_replace($tenant->subdomain, $tenant->custom_domain, $url);
            
            return redirect($customUrl, 301);
        }

        return $next($request);
    }
}


namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * TenantServiceProvider
 * Register tenant-related services
 */
class TenantServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('tenant', function ($app) {
            $tenantId = session('active_tenant_id');
            
            if (!$tenantId) {
                return null;
            }

            return \App\Models\Tenant::find($tenantId);
        });
    }

    public function boot()
    {
        // Register global helper
        if (!function_exists('tenant')) {
            function tenant(): ?\App\Models\Tenant
            {
                return app('tenant');
            }
        }

        if (!function_exists('tenantId')) {
            function tenantId(): ?int
            {
                return session('active_tenant_id');
            }
        }
    }
}
