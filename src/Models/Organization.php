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
    use \App\Components\RBAC;
    
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
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [];
    }
}
