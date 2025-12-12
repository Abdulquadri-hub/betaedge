<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('tenant', function ($app) {
            $tenantId = session('active_tenant_id');
            
            if (!$tenantId) {
                return null;
            }

            return Tenant::find($tenantId);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register global helper
        if (!function_exists('tenant')) {
            function tenant(): ?Tenant {
                return app('tenant');
            }
        }

        if (!function_exists('tenantId')) {
            function tenantId(): ?int {
                return session('active_tenant_id');
            }
        }
    }
}
