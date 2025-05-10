<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Log;

class TenantUserRegisterController extends Controller
{
    public function register(Request $request)
    {
        \Log::info('Starting user registration process');
        
        $tenant = tenant();
        // return ['tenant data' => $tenant];
        // die();

        if (!$tenant) {
            \Log::error('Tenant not found during registration');
            return response()->json(['message' => 'Tenant not initialized'], 400);
        }

        \Log::info('Registering user for tenant', [
            'tenant_id' => $tenant->id,
            'database' => DB::connection()->getDatabaseName(),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            
            
            \Log::info('User created successfully', ['user_id' => $user->id]);
            
            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user->makeHidden(['password']),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'tenant' => $tenant->id
            ]);
            return response()->json(['message' => 'Registration failed'], 500);
        }
    }

    public function testDB(Request $request)
    {
        return response()->json([
                    'connection' => DB::connection()->getName(),
                    'database' => DB::connection()->getDatabaseName(),
                    'config' => config('database.connections.'.DB::connection()->getName())
                ]);
    }
}
