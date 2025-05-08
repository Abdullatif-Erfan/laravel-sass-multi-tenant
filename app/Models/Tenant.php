<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;
    protected $fillable = [
        'id',
        'name',
        'email',
        'data',
    ];
    
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'data',
        ];
    }

    protected $casts = [
        'id' => 'string',
        'data' => 'array',
    ];

    public function getDatabaseName(): string
    {
        return 'tenant' . $this->id;
    }


    // public function database(): DatabaseConfig
    // {
    //     return new DatabaseConfig([
    //         'driver' => 'pgsql',
    //         'host' => env('DB_HOST', '127.0.0.1'),
    //         'port' => env('DB_PORT', '5432'),
    //         'database' => 'tenant' . $this->id,
    //         'username' => env('DB_USERNAME', 'postgres'),
    //         'password' => env('DB_PASSWORD', ''),
    //     ]);
    // }
}