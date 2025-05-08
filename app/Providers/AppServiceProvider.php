<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Events\TenancyInitialized;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot()
    {
        // \Event::listen(\Stancl\Tenancy\Events\TenancyInitialized::class, function ($event) {
        //     $tenant = $event->tenancy->tenant;
        //     $tenantDbName = $tenant->database;
        
        //     config(['database.connections.tenant.database' => $tenantDbName]);
        
        //     DB::purge('tenant');
        //     DB::reconnect('tenant');
        // });
    }
}



