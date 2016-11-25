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
    protected $fillable = ['type', 'status', 'metadata', 'external_id'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Party types.
     */
    const TYPES = ['user', 'organization'];
    
    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [
            'type' => 'string|required|in:' . implode(',', self::TYPES),
            'status' => 'integer|required|in:0,1',
            'metadata' => 'json|nullable',
            'external_id' => 'string|nullable|max:100'
        ];
    }

    /**
     * A party has one organization.
     */
    public function organization()
    {
        return $this->hasOne('F3\Models\Organization', 'party_id');
    }

    /**
     * A party has many bank accounts.
     */
    public function bankAccounts()
    {
        return $this->hasMany('F3\Models\BankAccount');
    }

    /**
     * Active scope.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
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
            ->pluck('permissions', 'name')
            ->toArray();

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
     * Creates a new party.
     * @param string $type user|organization
     * @param int $status 0|1
     * @param string $metadata Json-encoded array of key-value pair attributes
     * @param string $external_id Exnternal ID
     * @param array $relationships Array of relationships the new party will be tied to
     */
    public static function store($type, $status = 1, $metadata = null, $external_id = null, array $relationships = [])
    {
        try {
            // Start the transaction.
            DB::beginTransaction();
            
            // Build the attribute list.
            $attributes = [
                'type' => $type,
                'status' => $status,
                'external_id' => $external_id,
                'metadata' => $metadata
            ];

            // Create the party.
            $party = self::create($attributes);

            // Create the relationships.
            if ($relationships) {
                foreach ($relationships as $rel) {
                    Relationship::store($party->id, $rel['type'], $rel['to_party_id']);
                }
            }

            // Commit and return the party.
            DB::commit();
            return $party;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Creates a new bank account.
     * @param int $bank_id Bank ID
     * @param int $currency Three-letter currency code
     * @param int $account_number Account number
     * @param int $first_name Account first name
     * @param int $last_name Account last name
     * @param int $branch Bank branch
     * @param int $type savings|current
     * @param int $class personal|business
     */
    public function addBankAccount($bank_id, $currency, $account_number, $first_name, $last_name, $branch = null, $type = null, $class = null)
    {
        return BankAccount::store($this->id, $bank_id, $currency, $account_number, $first_name, $last_name, $branch, $type, $class);
    }
}
