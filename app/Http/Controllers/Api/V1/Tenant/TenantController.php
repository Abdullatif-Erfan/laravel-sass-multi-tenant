<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\Exceptions\TenantDatabaseAlreadyExistsException;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager;

class TenantController extends Controller
{
    protected function setupTenantDatabase(Tenant $tenant, string $password)
    {
        try {
            // 1. Create the physical database
            $databaseManager = app(PostgreSQLDatabaseManager::class);
            $databaseManager->setConnection('pgsql');
            $databaseManager->createDatabase($tenant);

            // 2. MANUALLY set the tenant database name
            config(['database.connections.tenant.database' => $tenant->getDatabaseName()]);
            \DB::purge('tenant'); // Clear connection cache


            // 3. Verify connection (debug)
            tenancy()->initialize($tenant);
            \DB::connection('tenant')->reconnect(); // Force fresh connection

            // dd([
            //     'current_tenant' => tenant('id'),
            //     'current_db' => \DB::connection('tenant')->select('SELECT current_database()')[0]->current_database,
            //     'expected_db' => $tenant->getDatabaseName(),
            //     'connection_config' => config('database.connections.tenant')
            // ]);

            // 4. Run ONLY tenant-specific migrations
            $this->runTenantMigrations($tenant);

            // 5. Create tenant admin, incase you need to register default admin for each tenant
            // $this->createTenantAdmin($tenant, $password);

        } catch (\Exception $e) {
            // PROPERLY END ALL CONNECTIONS BEFORE DELETION
            tenancy()->end(); // Critical - closes tenant connection
            \DB::disconnect('tenant'); // Disconnect explicitly
            
            if (isset($databaseManager)) {
                try {
                    // Kill all active connections to the tenant DB
                    \DB::connection('pgsql')->statement(
                        "SELECT pg_terminate_backend(pg_stat_activity.pid) 
                        FROM pg_stat_activity 
                        WHERE pg_stat_activity.datname = '{$tenant->getDatabaseName()}'
                        AND pid <> pg_backend_pid()"
                    );
                    sleep(1); // Give PostgreSQL time to close connections
                    $databaseManager->deleteDatabase($tenant);
                } catch (\Exception $deleteException) {
                    // Log deletion error if needed
                }
            }
            throw $e;
        } finally {
            tenancy()->end(); // End tenancy to clean up
            \DB::disconnect('tenant');
        }
    }

    protected function runTenantMigrations(Tenant $tenant)
    {
        // Clear caches (optional) 
        \Artisan::call('config:clear');
        \Artisan::call('route:clear');

        // Dynamically set the tenant database
        $tenantDatabase = $tenant->getDatabaseName();

        config(['database.connections.tenant.database' => $tenantDatabase]);

        // Purge and set the tenant connection
        \DB::purge('tenant');
        \DB::setDefaultConnection('tenant');
        \DB::reconnect('tenant');


        \Log::info("Running tenant migration", [
            'tenant_id' => $tenant->id,
            'database' => $tenantDatabase,
        ]);

        try {
            // Check DB connection
            \DB::connection('tenant')->getPdo();
            \Log::info("✅ Connected to tenant DB: $tenantDatabase");
        } catch (\Exception $e) {
            \Log::error("❌ Failed to connect to tenant DB: " . $e->getMessage());
            throw $e;
        }

        // Run tenant-specific migrations using --database and --path
        $exitCode = \Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',  
            '--force' => true,
            '--pretend' => false, 
        ]);
    }


    protected function createTenantAdmin(Tenant $tenant, string $password)
    {
        \App\Models\User::create([
            'name' => 'Admin',
            'email' => $tenant->email ?? 'admin@' . $tenant->getTenantKey() . '.com',
            'password' => bcrypt($password),
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email',
            'domain' => 'required|string|unique:domains,domain',
            'password' => 'required|string|min:8',
        ]);

        \DB::beginTransaction();

        try {
            // Create tenant
            $tenant = Tenant::create([
                'id' => (string) Str::uuid(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'data' => ['password' => $validated['password']], // Store password temporarily in 'data' field
            ]);

            if(!$tenant)
            {
                return response()->json([
                    'message' => 'Tenant is not created',
                ], 202);
            }

            // Create domain for the tenant
            $tenant->domains()->create([
                'domain' => $validated['domain'],
            ]);

            \DB::commit();

            \Log::info("Migrating tenant: " . $tenant->id);
            \Log::info("Creating database: " . $tenant->database()->getName());

            // Set up tenant DB (outside of transaction)
            $this->setupTenantDatabase($tenant, $validated['password']);

            return response()->json([
                'message' => 'Tenant created successfully',
                'tenant' => $tenant->only(['id', 'name', 'email']),
            ], 201);

        } catch (\Exception $e) {
            \DB::rollBack();

            // Delete tenant if creation partially succeeded
            if (isset($tenant)) {
                $tenant->delete();
            }

            return response()->json([
                'message' => 'Tenant creation failed',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}
