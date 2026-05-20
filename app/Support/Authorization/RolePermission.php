<?php

namespace App\Support\Authorization;

use App\Models\User;

class RolePermission
{
    public static function roles(): array
    {
        return config('permissions.roles', []);
    }

    public static function permissions(): array
    {
        return config('permissions.permissions', []);
    }

    public static function forRole(?string $role): array
    {
        return config("permissions.role_permissions.{$role}", []);
    }

    public static function userCan(User $user, string $permission): bool
    {
        return in_array($permission, self::forRole($user->role), true);
    }
}
