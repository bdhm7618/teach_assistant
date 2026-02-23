<?php

namespace Modules\Channel\App\Traits;

trait HasPermissions
{
    /**
     * Check if the user has a specific permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->hasPermission($permission);
    }

    /**
     * Check if the user has any of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->hasAnyPermission($permissions);
    }

    /**
     * Check if the user has all of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->hasAllPermissions($permissions);
    }

    /**
     * Check if the user is an owner (has all permissions).
     *
     * @return bool
     */
    public function isOwner(): bool
    {
        return $this->role && $this->role->name === 'owner';
    }
}

