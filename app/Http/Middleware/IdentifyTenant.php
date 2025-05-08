<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = $request->header('X-Tenant-ID'); // or use $request->get('tenant_id')

        if (!$tenantId) {
            abort(400, 'Tenant ID is missing.');
        }

        try {
            tenancy()->initialize($tenantId);
        } catch (TenantCouldNotBeIdentifiedById $e) {
            abort(404, 'Tenant not found.');
        }
        
        return $next($request);
    }
}

