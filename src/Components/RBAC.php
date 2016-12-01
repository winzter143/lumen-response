<?php
namespace F3\Components;

use DB;

/**
 * RBAC trait.
 */
trait RBAC
{
    /**
     * Returns the roles assigned to the party.
     */
    public function getRoles()
    {
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
    public function can($permission, $party_id = null)
    {
        // Get the user roles.
        $user_roles = array_flatten($this->getRoles());

        // Check if the user is assigned the permission.
        $has_permission = in_array($permission, $user_roles);

        // Check if the user can view any party in the system.
        // "view-party" is a special role in the system that is assigned only to system users.
        if (in_array('view-party', $user_roles)) {
            $can_view_party = true;
        } else {
            $can_view_party = ($this->party_id == $party_id);
        }

        // Check if the user has the permission and can view the party.
        return ($has_permission && $can_view_party);
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
}
