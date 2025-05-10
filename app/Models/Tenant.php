<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

    protected $casts = [
        'id' => 'string',
        'data' => 'array',
    ];

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

    public function getDatabaseName(): string
    {
        return 'tenant' . $this->id;
    }   
}