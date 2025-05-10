<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Tenant\TenantController;
use App\Http\Controllers\Api\V1\User\TenantUserRegisterController;
use App\Http\Controllers\Api\V1\Auth\AuthController;


Route::prefix('v1')->group(function () {
    Route::post('/register-tenant', [TenantController::class, 'register']);

    // Tenant context required (apply `tenancy` middleware)
    Route::middleware('tenancy')->group(function () {
        Route::post('/tenant-user-register', [TenantUserRegisterController::class, 'register']);
        // Route::middleware(['auth:sanctum', 'universal'])->group(function () 
        // {
        //     Route::get('/account', [AuthController::class, 'account']);
        // });

        // Route::get('/test-db',function(){
        //     return response()->json([
        //         'connection' => DB::connection()->getName(),
        //         'database' => DB::connection()->getDatabaseName(),
        //         'config' => config('database.connections.'.DB::connection()->getName())
        //     ]);
        // });

        Route::get('/test-db',[TenantUserRegisterController::class, 'testDB']);
       


    });
});

// Route::prefix('v1')->group(function () {
//     Route::get('/test-db', function() {
//         return [
//             'tenant' => tenant('id'),
//             'current_db' => DB::connection()->getDatabaseName(),
//             'db_connection' => DB::connection()->getName(),
//             'config' => config('database.connections.tenant'),
//             // 'all_tenants' => \Stancl\Tenancy\Src\Tenant::all()->pluck('id'),
//         ];
//     })->middleware('identify.tenant');
// });

