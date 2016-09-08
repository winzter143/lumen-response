<?php
namespace F3\Models;

use DB;
use Illuminate\Support\Facades\Hash;
use F3\Components\Model;

class User extends Model
{
    /**
     * RBAC trait.
     * This class is shared between the User and Organization models.
     */
    use \F3\Components\RBAC;

    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'core.users';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['party_id', 'login_id', 'email', 'first_name', 'last_name', 'roles', 'api_key', 'secret_key'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'party_id';

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [];
    }

    /**
     * Validates the user's credentials.
     */
    public static function authenticate($login_id, $password, $role, $org_party_id)
    {
        // Look for the user.
        $user = DB::table('core.users as u')
            ->select(['u.party_id', 'u.login_id', 'u.password', 'u.email', 'u.first_name', 'u.last_name', 'k.api_key', 'k.secret_key', 'p.type'])
            ->join('core.parties as p', 'p.id', '=', 'u.party_id')
            ->join('core.api_keys as k', 'k.party_id', '=', 'u.party_id')
            ->where([['u.login_id', $login_id], ['p.status', 1]])
            ->first();

        // Check if the user exists.
        if (!$user) {
            throw new \Exception('The credentials that you provided are invalid or your account may have been disabled.', 401);
        }

        // Check the password.
        if (!Hash::check($password, $user['password'])) {
            throw new \Exception('The credentials that you provided are invalid or your account may have been disabled.', 401);
        }

        // Check if the user is a member of the organization.
        $relationship = DB::table('core.relationships')
            ->select('id')
            ->where([['from_party_id', $user['party_id']], ['type', 'employee_of'], ['to_party_id', $org_party_id]])
            ->first();

        if (!$relationship) {
            throw new \Exception('The user is not a member of the system organization.', 401);
        }

        // Get the roles.
        $user['roles'] = DB::table('core.party_roles as pr')
            ->select('r.name', 'r.permissions')
            ->join('core.roles as r', 'r.id', '=', 'pr.role_id')
            ->where([['pr.party_id', $user['party_id']]])
            ->pluck('permissions', 'name');

        // Check the role.
        if (!in_array($role, array_keys($user['roles']))) {
            throw new \Exception('The role that you provided is not assigned to the user.', 401);
        }

        // Decode the permissions.
        $user['roles'] = array_map(function($permission) {
            return json_decode($permission);
        }, $user['roles']);

        // Remove the password and return the user.
        unset($user['password']);
        return $user;
    }

    /**
     * Creates a new user.
     */
    public static function store()
    {
        try {
            // Build the attribute list.
            $attributes = [];

            // Create the user. 
            return self::create($attributes);
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }
}
