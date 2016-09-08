<?php
namespace F3\Components;

/**
 * RBAC trait.
 */
trait RBAC
{
    /**
     * Checks if the role is assigned to the user.
     * @param string|array $roles
     */
    public function hasRole($roles)
    {
        // Convert $roles to an array.
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        // Check if the user is assigned the role.
        foreach ($roles as $role) {
            if (in_array($role, array_keys($this->roles))) {
                return true;
            }
        }

        // None of the roles are assigned to the user.
        return false;
    }

    /**
     * Checks if the permission is assigned to the user.
     * @param string|array $permissions
     */
    public function can($permissions)
    {
        // Convert $permissions to an array.
        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }

        // Check if the user is assigned the permission.
        foreach ($permissions as $permission) {
            if (in_array($permission, array_flatten($this->roles))) {
                return true;
            }
        }

        // None of the permissions are assigned to the user.
        return false;
    }

    /**
     * Returns the party type.
     */
    public function getType()
    {
        return strtolower(str_replace('App\Models\\', '', __CLASS__));
    }

    /**
     * Checks if the party is a user.
     */
    public function isUser()
    {
        return ($this->getType() == 'user');
    }

    /**
     * Checks if the party is an organization.
     */
    public function isOrganization()
    {
        return ($this->getType() == 'organization');
    }
}
