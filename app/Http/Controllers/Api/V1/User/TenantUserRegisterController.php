<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Log;

class TenantUserRegisterController extends Controller
{
    public function register(Request $request)
    {
        $tenant = tenant(); // this is now initialized and DB switched
        return ['tenants' => $tenant];

        // if (! $tenant) {
        //     return response()->json(['message' => 'Tenant not found'], 400);
        // }

        // // Log tenant ID and current DB connection details
        // Log::info('Registering user for tenant', [
        //     'tenant_id' => $tenant->id,
        //     'connection_name' => config('database.default'),
        //     'tenant_connection' => config('database.connections.tenant.database'),
        //     'actual_connection_used' => \DB::connection()->getName(),
        //     'actual_database_name' => \DB::connection()->getDatabaseName(),
        // ]);

        // $validated = $request->validate([
        //     'name' => 'required|string|max:255',
        //     'email' => 'required|email',
        //     'password' => ['required', 'confirmed', Rules\Password::defaults()],
        // ]);

        // $user = User::create([
        //     'name' => $validated['name'],
        //     'email' => $validated['email'],
        //     'password' => Hash::make($validated['password']),
        // ]);
        // App\Models\Tenant::all()->runForEach(function () {
        //     dd('Creating user for tenant');
        //     // App\Models\User::create([
        //     //     'name' => $validated['name'],
        //     //     'email' => $validated['email'],
        //     //     'password' => Hash::make($validated['password']),
        //     // ]);
        // });

        // return response()->json([
        //     'message' => 'User registered successfully',
        //     'tenant_id' => $tenant->id,
        //     'user' => $user->makeHidden(['password']),
        // ]);
    }

}
