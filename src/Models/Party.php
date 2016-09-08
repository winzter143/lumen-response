<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Party extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'core.parties';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [];
    }

    /**
     * Returns the party by API key.
     */
    public static function getByApiKey($api_key)
    {
        // Get the party by key.
        $party = DB::table('core.api_keys as k')
            ->select(['p.id', 'p.type', 'k.api_key', 'k.secret_key'])
            ->join('core.parties as p', 'p.id', '=', 'k.party_id')
            ->where([['k.api_key', $api_key], ['k.status', 1], ['p.status', 1]])
            ->first();

        if (!$party) {
            return false;
        }

        switch ($party['type']) {
        case 'user':
            // Get the user.
            $result = DB::table('core.users as u')
                ->select(['u.party_id', 'u.login_id', 'u.email', 'u.first_name', 'u.last_name'])
                ->join('core.parties as p', 'p.id', '=', 'u.party_id')
                ->where([['u.party_id', $party['id']], ['p.status', 1]])
                ->first();
            break;
        case 'organization':
            // Get the organization.
            $result = DB::table('core.organizations as o')
                ->select(['o.party_id', 'o.name'])
                ->join('core.parties as p', 'p.id', '=', 'o.party_id')
                ->where([['o.party_id', $party['id']], ['p.status', 1]])
                ->first();
            break;
        default:
            return false;
        }

        // Get the roles.
        $result['roles'] = DB::table('core.party_roles as pr')
            ->select('r.name', 'r.permissions')
            ->join('core.roles as r', 'r.id', '=', 'pr.role_id')
            ->where([['pr.party_id', $result['party_id']]])
            ->pluck('permissions', 'name');

        // Decode the permissions.
        if ($result['roles']) {
            $result['roles'] = array_map(function($permission) {
                return json_decode($permission);
            }, $result['roles']);
        }

        if ($result) {
            // Merge the results.
            $result = array_merge($result, ['api_key' => $party['api_key'], 'secret_key' => $party['secret_key'], 'type' => $party['type']]);
            $object = '\F3\Models\\' . ucfirst($party['type']);
            return new $object($result);
        } else {
            return false;
        }
    }

    /**
     * Parses the metadata and returns the value of the requested key.
     * @param string $key
     */
    public static function getMetadata($party_id, $key)
    {
        // Look for the party.
        $metadata = DB::table('core.parties')->where('id', $party_id)->value('metadata');

        if (!$metadata) {
            return false;
        }

        // Decode the metadata.
        $metadata = json_decode($metadata, true);

        // Return the value of the requested key.
        return isset($metadata[$key]) ? $metadata[$key] : false;
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
