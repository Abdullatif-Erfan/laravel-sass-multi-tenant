<?php

declare(strict_types=1);

// use Illuminate\Support\Facades\Route;
// use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
// use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

// Route::middleware([
//     'web',
//     InitializeTenancyByRequestData::class, // initialize tenancy from request data
//     PreventAccessFromCentralDomains::class,
// ])->group(function () {
//     Route::get('/', function () {
//         return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
//     });
// });

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'api', // ✅ Use API middleware for REST APIs
    InitializeTenancyByRequestData::class,
    PreventAccessFromCentralDomains::class,
]);

// use Illuminate\Support\Facades\Route;
// use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
// use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

// Route::middleware([
//     'api',
//     InitializeTenancyByRequestData::class,
//     PreventAccessFromCentralDomains::class,
// ])->group(function () {
//     Route::get('/', function () {
//         return response()->json([
//             'message' => 'Tenant ID: ' . tenant('id'),
//         ]);
//     });

//     // Your actual tenant API routes (e.g., registration, login)
//     Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register']);
// });
