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

/**
 * Controller responsible for tenant registration and database setup
 * in a multi-tenant Laravel application using stancl/tenancy.
 */
class TenantController extends Controller
{
    /**
     * Registers a new tenant and sets up the corresponding database.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email',
            'domain' => 'required|string|unique:domains,domain',
            'password' => 'required|string|min:8',
        ]);

        DB::beginTransaction();

        try {
            // Generate UUID for tenant ID and database
            $tenantId = (string) Str::uuid();
            $databaseName = 'tenant' . $tenantId;

            // Create the tenant model
            $tenant = Tenant::create([
                'id' => $tenantId,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'data' => [
                    'database' => $databaseName
                ],
            ]);

            if (!$tenant) {
                return response()->json([
                    'message' => 'Tenant is not created',
                ], 202);
            }

            // Associate domain with tenant
            $tenant->domains()->create([
                'domain' => $validated['domain'],
            ]);

            DB::commit();

            Log::info("Migrating tenant: " . $tenant->id);
            Log::info("Creating database: " . $tenant->database()->getName());

            // Set up tenant database and run migrations
            $this->setupTenantDatabase($tenant, $validated['password']);

            return response()->json([
                'message' => 'Tenant created successfully',
                'tenant' => $tenant->only(['id', 'name', 'email']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up partially created tenant
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

    /**
     * Sets up the tenant database and runs migrations.
     *
     * @param Tenant $tenant
     * @param string $password
     * @throws \Exception
     */
    protected function setupTenantDatabase(Tenant $tenant, string $password)
    {
        try {
            // Initialize the PostgreSQL database manager
            $databaseManager = app(PostgreSQLDatabaseManager::class);
            $databaseManager->setConnection('pgsql');

            // Create the tenant's database
            $databaseManager->createDatabase($tenant);

            // Manually configure the tenant connection
            config(['database.connections.tenant.database' => $tenant->getDatabaseName()]);
            DB::purge('tenant');

            // Initialize tenancy and reconnect
            tenancy()->initialize($tenant);
            DB::connection('tenant')->reconnect();

            // Run tenant-specific migrations
            $this->runTenantMigrations($tenant);

            // Optionally create a default tenant admin
            // $this->createTenantAdmin($tenant, $password);

        } catch (\Exception $e) {
            // Ensure connections are properly closed
            tenancy()->end();
            DB::disconnect('tenant');

            // Attempt to delete the database
            if (isset($databaseManager)) {
                try {
                    DB::connection('pgsql')->statement(
                        "SELECT pg_terminate_backend(pg_stat_activity.pid)
                         FROM pg_stat_activity
                         WHERE pg_stat_activity.datname = '{$tenant->getDatabaseName()}'
                         AND pid <> pg_backend_pid()"
                    );
                    sleep(1); // Ensure connections are terminated
                    $databaseManager->deleteDatabase($tenant);
                } catch (\Exception $deleteException) {
                    // Log cleanup error if necessary
                }
            }

            throw $e;
        } finally {
            tenancy()->end();
            DB::disconnect('tenant');
        }
    }

    /**
     * Runs tenant-specific database migrations.
     *
     * @param Tenant $tenant
     * @throws \Exception
     */
    protected function runTenantMigrations(Tenant $tenant)
    {
        // Optional: clear caches to avoid conflicts
        \Artisan::call('config:clear');
        \Artisan::call('route:clear');

        // Set the tenant DB connection
        $tenantDatabase = $tenant->getDatabaseName();
        config(['database.connections.tenant.database' => $tenantDatabase]);
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');
        DB::reconnect('tenant');

        Log::info("Running tenant migration", [
            'tenant_id' => $tenant->id,
            'database' => $tenantDatabase,
        ]);

        try {
            // Test DB connection
            DB::connection('tenant')->getPdo();
            Log::info("Connected to tenant DB: $tenantDatabase");
        } catch (\Exception $e) {
            Log::error("Failed to connect to tenant DB: " . $e->getMessage());
            throw $e;
        }

        // Execute tenant migrations
        \Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
            '--pretend' => false,
        ]);
    }

    /**
     * Optionally creates a default admin user for the tenant.
     *
     * @param Tenant $tenant
     * @param string $password
     */
    protected function createTenantAdmin(Tenant $tenant, string $password)
    {
        \App\Models\User::create([
            'name' => 'Admin',
            'email' => $tenant->email ?? 'admin@' . $tenant->getTenantKey() . '.com',
            'password' => bcrypt($password),
        ]);
    }
}
