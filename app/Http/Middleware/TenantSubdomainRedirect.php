<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantSubdomainRedirect
{
    public function handle(Request $request, Closure $next): Response
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
