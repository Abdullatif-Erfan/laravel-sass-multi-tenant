<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Exceptions\TenantDatabaseAlreadyExistsException;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;

class TenantController extends Controller
{
    protected function setupTenantDatabase(Tenant $tenant)
    {
        try {
            // Get the database manager
            $databaseManager = app(\Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class);
            $databaseManager->setConnection('pgsql');
            
            // Create the database
            $databaseManager->createDatabase($tenant);
            
            // Initialize tenancy
            tenancy()->initialize($tenant);
            
            // Run tenant-specific migrations
            \Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--database' => 'tenant',
                '--force' => true,
            ]);
            
            // Safely access tenant data with null coalescing
            $tenantPassword = $tenant->data['password'] ?? bcrypt(Str::random(16));
            
            // Create default admin user for the tenant
            \App\Models\User::create([
                'name' => 'Admin',
                'email' => $tenant->email ?? 'admin@'.$tenant->getTenantKey().'.com',
                'password' => $tenantPassword,
            ]);
            
        } catch (\Exception $e) {
            throw $e; // Re-throw for handling in the main method
        } finally {
            tenancy()->end();
        }
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
            // Create tenant with properly structured data
            $tenant = Tenant::create([
                'id' => Str::uuid(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'data' => [
                    'password' => bcrypt($validated['password']),
                    // Add other tenant-specific data here
                ],
            ]);
            
            $tenant->domains()->create([
                'domain' => $validated['domain'],
            ]);
            
            // Create database outside transaction
            \DB::commit();
            $this->setupTenantDatabase($tenant);
            
            return response()->json([
                'message' => 'Tenant created successfully',
                'tenant' => $tenant,
            ], 201);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            // Delete tenant if it was created but other steps failed
            if (isset($tenant)) {  // Fixed: Added missing closing parenthesis
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