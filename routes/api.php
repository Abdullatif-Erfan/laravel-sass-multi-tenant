<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Tenant\TenantController;
use App\Http\Controllers\Api\V1\Auth\AuthController;


// Route::post('/register-tenant', [TenantController::class, 'register']);

Route::prefix('v1')->group(function () {
    // Tenant registration (no auth needed)
    Route::post('/register-tenant', [TenantController::class, 'register']);
    
    // Authentication routes
    Route::post('/tenant-user-register', [AuthController::class, 'register']);
    Route::post('/tenant-user-login', [AuthController::class, 'login']);
    
    
    // Authenticated routes
    Route::middleware(['auth:sanctum', 'universal'])->group(function () {
        Route::get('/account', [AuthController::class, 'account']);
    });
});