<?php

namespace App\Http\Middleware;

use Closure;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;
use Stancl\Tenancy\Tenancy;

class IdentifyTenant
{
    public function handle($request, Closure $next)
    {
        // Check for tenant ID in header or in tokenable_id for Sanctum tokens
        if ($request->hasHeader('X-Tenant-ID')) {
            $tenantId = $request->header('X-Tenant-ID');
            tenancy()->initialize($tenantId);
        } elseif ($request->user()) {
            // If user is authenticated via Sanctum, initialize their tenant
            tenancy()->initialize($request->user()->tenant_id);
        }
        
        return $next($request);
    }
}