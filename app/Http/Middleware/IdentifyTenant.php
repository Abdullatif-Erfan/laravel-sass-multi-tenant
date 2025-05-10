<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedException;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{

    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        if (!$request->hasHeader('X-Tenant-ID')) {
            return response()->json(['message' => 'X-Tenant-ID header required'], 400);
        }

        $tenantId = $request->header('X-Tenant-ID');
        $dbName = 'tenant' . $tenantId;

        try {
            
            // 1. Set the tenant database name
            config(['database.connections.tenant.database' => $dbName]);
            tenancy()->initialize($tenantId);
            
            
            // 2. Purge and reconnect the tenant connection
            DB::purge('tenant');
            DB::reconnect('tenant');
            
            // 3. Set tenant connection as default
            DB::setDefaultConnection('tenant');
            
            // 4. Verify connection
            DB::connection()->getPdo(); // Now uses default tenant connection
            
            \Log::info('Database switched', [
                'tenant' => $tenantId,
                'database' => DB::connection()->getDatabaseName(),
                'connection' => DB::connection()->getName()
            ]);
            
            return $next($request);
        } catch (\Exception $e) {
            \Log::error('Database switch failed', [
                'error' => $e->getMessage(),
                'tenant' => $tenantId
            ]);
            return response()->json(['message' => 'Database switch failed'], 500);
        }
    }

    // public function handle(Request $request, Closure $next): Response
    // {
    //     if ($request->hasHeader('X-Tenant-ID')) {
    //         $tenantId = $request->header('X-Tenant-ID');

    //         try {
    //             tenancy()->initialize($tenantId);

    //             $tenant = tenancy()->tenant;
    //             $tenantDatabase = $tenant->getDatabaseName(); 

    //             config(['database.connections.tenant.database' => $tenantDatabase]);
    //             \DB::purge('tenant');
    //             \DB::reconnect('tenant');

    //             \Log::info('Tenant initialized', [
    //                 'tenant_id' => $tenantId,
    //                 'connection' => \DB::connection()->getName(),
    //                 'database' => \DB::connection()->getDatabaseName(),
    //             ]);
    //         } catch (\Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedException $e) {
    //             return response()->json([
    //                 'message' => 'Invalid or missing tenant',
    //                 'error' => $e->getMessage(),
    //             ], Response::HTTP_BAD_REQUEST);
    //         } catch (\Exception $e) {
    //             \Log::error('Tenant initialization failed', [
    //                 'tenant_id' => $tenantId,
    //                 'error' => $e->getMessage(),
    //             ]);
    //             return response()->json([
    //                 'message' => 'Failed to switch to tenant database',
    //                 'error' => $e->getMessage(),
    //             ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //         }
    //     } else {
    //         return response()->json([
    //             'message' => 'X-Tenant-ID header is required',
    //         ], Response::HTTP_BAD_REQUEST);
    //     }

    //     return $next($request);
    // }
    // public function terminate($request, $response): void
    // {
    //     // Properly end tenancy after the request
    //     tenancy()->end();
    // }
}
