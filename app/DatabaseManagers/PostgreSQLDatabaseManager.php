<?php

namespace App\DatabaseManagers;

use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Database\TenantDatabaseManagers\Concerns\CreatesPostgreSQLDatabases;
use Stancl\Tenancy\Database\TenantDatabaseManagers\TenantDatabaseManager;

class PostgreSQLDatabaseManager extends TenantDatabaseManager
{
    use CreatesPostgreSQLDatabases;

    public function createDatabase(Tenant $tenant): bool
    {
        $databaseName = $this->databaseName($tenant);

        // Use a privileged connection to create the DB (connect to 'postgres' DB)
        config(['database.connections.pgsql.database' => 'postgres']);
        DB::purge('pgsql');

        return DB::connection('pgsql')->statement("CREATE DATABASE \"$databaseName\"");
    }

    public function deleteDatabase(Tenant $tenant): bool
    {
        $databaseName = $this->databaseName($tenant);

        config(['database.connections.pgsql.database' => 'postgres']);
        DB::purge('pgsql');

        return DB::connection('pgsql')->statement("DROP DATABASE IF EXISTS \"$databaseName\"");
    }

    protected function databaseName(Tenant $tenant): string
    {
        return $tenant->database()->getName(); // or customize this logic
    }
}
