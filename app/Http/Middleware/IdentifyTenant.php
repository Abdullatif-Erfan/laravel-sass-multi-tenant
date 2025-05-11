<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to identify and switch to the tenant's database
 * based on the `X-Tenant-ID` HTTP header.
 *
 * This middleware dynamically configures the tenant's database
 * connection and makes it the default for the request lifecycle.
 */
class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force all responses to be JSON
        $request->headers->set('Accept', 'application/json');

        // Check for the required tenant identifier in the request headers
        if (!$request->hasHeader('X-Tenant-ID')) {
            return response()->json(['message' => 'X-Tenant-ID header required'], 400);
        }

        // Retrieve tenant ID from the header
        $tenantId = $request->header('X-Tenant-ID');

        // Dynamically generate the tenant database name (e.g., tenant3)
        $dbName = 'tenant' . $tenantId;

        try {
            // Set the tenant database name in the configuration
            config(['database.connections.tenant.database' => $dbName]);

            // Initialize tenancy to prepare tenant-specific environment (e.g., loading tenant model)
            tenancy()->initialize($tenantId);

            // Purge existing connection and reconnect to ensure correct config is loaded
            DB::purge('tenant');
            DB::reconnect('tenant');

            // Set tenant connection as the default for the current request
            DB::setDefaultConnection('tenant');

            // Trigger the actual connection to ensure database exists and credentials are valid
            DB::connection()->getPdo(); // Uses the current default ('tenant')

            // Log the successful switch
            Log::info('Database switched', [
                'tenant' => $tenantId,
                'database' => DB::connection()->getDatabaseName(),
                'connection' => DB::connection()->getName()
            ]);

            // Continue processing the request
            return $next($request);
        } catch (\Exception $e) {
            // Log the failure for debugging
            Log::error('Database switch failed', [
                'error' => $e->getMessage(),
                'tenant' => $tenantId
            ]);

            // Return a generic error response
            return response()->json(['message' => 'Database switch failed'], 500);
        }
    }
}
