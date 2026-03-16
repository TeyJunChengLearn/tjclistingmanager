<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $casts = [
        'allowed_resources' => 'array',
    ];

    public function canAccessResource(string $resource): bool
    {
        return in_array($resource, $this->allowed_resources ?? []);
    }
}
