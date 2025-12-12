<?php

namespace App\Http\Middleware;

use Closure;
use Inertia\Inertia;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    private array $reservedSubdomains = [
        'www', 'api', 'admin', 'app', 'mail', 'ftp', 'smtp',
        'localhost', 'staging', 'dev', 'test', 'beta', 'demo'
    ];
    
    public function handle(Request $request, Closure $next)
    {
        $tenant = $this->detectTenant($request);

        if (!$tenant) {
            if ($this->isMainPlatformRequest($request)) {
                return $next($request);
            }

            abort(404, 'School not found');
        }

        if ($tenant->status !== 'active') {
            return response()->view('errors.tenant-inactive', [
                'tenant' => $tenant,
                'message' => 'This school account is currently inactive.'
            ], 403);
        }

        if ($tenant->isOnTrial() && $tenant->trial_ends_at < now()) {
            return response()->view('errors.trial-expired', [
                'tenant' => $tenant
            ], 403);
        }

        session(['active_tenant_id' => $tenant->id]);

        app()->instance('tenant', $tenant);
        
        $request->merge(['tenant' => $tenant]);

        Log::withContext(['tenant_id' => $tenant->id]);

        if ($tenant->timezone) {
            config(['app.timezone' => $tenant->timezone]);
            date_default_timezone_set($tenant->timezone);
        }

        Inertia::share([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'logo' => $tenant->logo,
                'primary_color' => $tenant->primary_color,
                'secondary_color' => $tenant->secondary_color,
                'subdomain' => $tenant->subdomain,
            ]
        ]);

        return $next($request);
    }

    protected function detectTenant(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $cacheKey = "tenant_host_{$host}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($host) {

            if(!$this->isAllowedDomain($host)) {
                return null;
            }

            $tenant = Tenant::where('custom_domain', $host)
                           ->where('status', 'active')
                           ->first();
    
            if ($tenant) {
                return $tenant;
            }
    
            if ($this->isSubdomain($host)) {
    
                $subdomain = $this->extractSubdomain($host);
    
                if (in_array($subdomain, $this->reservedSubdomains)) {
                    return null;
                }
    
                $tenant = Tenant::where('slug', $subdomain)
                               ->where('status', 'active')
                               ->first();
    
                return $tenant;
            }

        });

        return null;
    }

    protected function isSubdomain(string $host): bool
    {
        $mainDomain = config('app.main_domain', 'betaedge.test');
        return str_contains($host, $mainDomain) && $host !== $mainDomain;
    }

    protected function extractSubdomain(string $host): string
    {
        $mainDomain = config('app.main_domain', 'betaedge.test');
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

    protected function isAllowedDomain(string $host): bool
    {
        $mainDomain = config('app.main_domain'); 

        // Allow custom domains
        if (Tenant::where('custom_domain', $host)->exists()) {
            return true;
        }

        // Allow *.teach.com
        return str_ends_with($host, $mainDomain);
    }
}
