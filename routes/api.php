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
        Route::middleware(['auth:sanctum', 'universal'])->group(function () {
            Route::get('/account', [AuthController::class, 'account']);
        });
    });
});