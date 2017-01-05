<?php
namespace F3\Components;

use DB;

/**
 * RBAC trait.
 */
trait RBAC
{
    /**
     * User roles and scope.
     */
    private $roles = [];
    private $scope = [];

    /**
     * Sets the user roles.
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * Sets the user scope.
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * Returns the roles assigned to the party.
     */
    public function getRoles()
    {
        // Check if the roles have already been set.
        if ($this->roles) {
            return $this->roles;
        }

        // Get the roles.
        $roles = DB::table('core.party_roles as pr')
            ->select('r.name', 'r.permissions')
            ->join('core.roles as r', 'r.id', '=', 'pr.role_id')
            ->where([['pr.party_id', $this->party_id]])
            ->pluck('permissions', 'name')
            ->toArray();

        if ($roles) {
            // Decode the permissions.
            $roles = array_map(function($permission) {
                return json_decode($permission);
            }, $roles);
        } else {
            $roles = [];
        }

        // Set the roles.
        $this->roles = $roles;
        return $roles;
    }

    /**
     * Checks if the role is assigned to the user.
     * @param string|array $roles
     */
    public function hasRole($roles)
    {
        // Get the user roles.
        $user_roles = $this->getRoles();

        // Convert $roles to an array.
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        // Check if the user is assigned the role.
        foreach ($roles as $role) {
            if (in_array($role, array_keys($user_roles))) {
                return true;
            }
        }

        // None of the roles are assigned to the user.
        return false;
    }

    /**
     * Checks if the permission is assigned to the user.
     * @param string $permission
     * @param int $party_id
     */
    public function can($permission, $party_id = false)
    {
        // Get the user permissions.
        $permissions = array_flatten($this->getRoles());

        // Check if the user is assigned the permission.
        $has_permission = in_array($permission, $permissions);

        if ($party_id === false) {
            // No need to check the party.
            return $has_permission;
        }

        // Get the scope.
        $scope = $this->getScope();
        
        // Check if the user can view any party in the system.
        // "manage-party" is a special role in the system that is assigned only to system users.
        if (in_array('manage-party', $permissions)) {
            $can_manage_party = true;
        } else {
            if (is_null($scope['users'])) {
                $can_manage_party = true;
            } else {
                // Convert party_id to array.
                $party_id = is_array($party_id) ? $party_id : [$party_id];
                
                // Check if the party IDs are the same.
                $can_manage_party = !array_diff($party_id, $scope['users']);
            }
        }

        // Check if the user has the permission and can view the party.
        return ($has_permission && $can_manage_party);
    }

    /**
     * Revokes a party role.
     */
    public function revokeRole($role)
    {
        // Check if the role exists.
        $role = DB::table('core.roles')->where('name', $role)->first();

        if (!$role) {
            throw new \Exception('The role does not exist.');
        }

        // Delete the role.
        return DB::table('core.party_roles')->where([['party_id', $this->party_id], ['role_id', $role['id']]])->delete();
    }

    /**
     * Assigns a role to the party.
     */
    public function assignRole($role)
    {
        // Check if the role exists.
        $role = DB::table('core.roles')->where('name', $role)->first();

        if (!$role) {
            throw new \Exception('The role does not exist.');
        }

        // Check if the party exists and if it's active.
        $party = DB::table('core.parties')->where([['id', $this->party_id], ['status', 1]])->first();

        if (!$party) {
            throw new \Exception('The party does not exist or may have been disabled.');
        }

        // Check if the mapping exists.
        $result = DB::table('core.party_roles')->where([['party_id', $party['id']], ['role_id', $role['id']]])->first();

        if ($result) {
            // The mapping exists.
            return true;
        }

        try {
            // The mapping does not exist. Assign the role to the party.
            return DB::table('core.party_roles')->insert([
                ['party_id' => $party['id'], 'role_id' => $role['id']]
            ]);
        } catch (\Exception $e) {
            // Multiple inserts might still happen even if we checked earlier if the mapping exists.
            // Check if it's a constraint error.
            if ($e->getCode() == Model::PG_ERROR_UNIQUE_VIOLATION) {
                return true;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Returns the party type.
     */
    public function getType()
    {
        return strtolower(str_replace('F3\Models\\', '', __CLASS__));
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

    /**
     * Returns the list of entities the party has access to.
     */
    public function getScope()
    {
        // Check if the scope have already been set.
        if ($this->scope) {
            return $this->scope;
        }

        // Keep it simple for now.
        // If the user has access to all users if he has the "manage-party" permission.
        // Otherwise, he can only access his own records.
        if ($this->can('manage-party')) {
            $scope['users'] = null;
        } else {
            $scope['users'] = [$this->party_id];
        }

        // Set the scope.
        $this->scope = $scope;
        return $scope;
    }
}
