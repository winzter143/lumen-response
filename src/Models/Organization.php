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
     * Class constants.
     */
    const DEFAULT_CONTRACT = [
        'shipping_fee' => [
            'manila' => 100,
            'provincial' => 150
        ],
        'insurance_fee' => [
            'type' => 'percent',
            'value' => 0.01,
            'max' => 5
        ],
        'transaction_fee' => [
            'type' => 'percent',
            'value' => 0.03,
            'max' => 20
        ],
        'pickup_retries' => 3,
        // Within 7 days of delivery.
        'claim_period' => 7
    ];
    
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
