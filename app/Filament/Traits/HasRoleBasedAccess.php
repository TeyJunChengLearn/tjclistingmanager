<?php

namespace App\Filament\Traits;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;

trait HasRoleBasedAccess
{
    private static array $roleCache = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return true;
        }

        if (!isset(static::$roleCache[$user->id])) {
            static::$roleCache[$user->id] = Role::where('name', $user->role)->first();
        }

        $role = static::$roleCache[$user->id];

        if ($role?->canAccessResource(static::class)) {
            return true;
        }

        // Check group access: "location_group" grants access to all Location resources
        if (str_contains(static::class, 'Resources\\Location\\')) {
            return $role?->canAccessResource('location_group') ?? false;
        }

        // Check group access: "my_listing_group" grants access to all My Listing resources
        if (str_contains(static::class, 'Resources\\MyListing\\')) {
            return $role?->canAccessResource('my_listing_group') ?? false;
        }

        return false;
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDeleteAny(): bool
    {
        return static::canAccess();
    }
}
