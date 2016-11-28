<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Organization extends Model
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
    protected $table = 'core.organizations';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['party_id', 'name', 'roles', 'api_key', 'secret_key'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'party_id';

    /**
     * Don't update the timestamps automatically.
     */
    public $timestamps = false;

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [
            'party_id' => 'integer|required|exists:pgsql.core.parties,id',
            'name' => 'string|nullable|max:255|unique:pgsql.core.organizations,name',
        ];
    }

    /**
     * An organization belongs to a party.
     */
    public function organization()
    {
        return $this->hasOne('F3\Models\Party');
    }

    /**
     * An organization has many orders.
     */
    public function orders()
    {
        return $this->hasMany('F3\Models\Order', 'party_id', 'party_id');
    }

    /**
     * Creates a new organization.
     * @param string $name Organization name
     * @param string $external_id External ID
     * @param array $relationships Array of relationships
     */
    public static function store($name = null, $external_id = null, array $relationships = [])
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Create the party.
            $party = Party::store('organization', 1, null, $external_id, $relationships);

            // Build the organization attribute list.
            $attributes = [
                'party_id' => $party->id,
                'name' => $name
            ];

            // Create the organization. 
            $org = self::create($attributes);

            // Commit and return the organization.
            DB::commit();
            return $org;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }
}
