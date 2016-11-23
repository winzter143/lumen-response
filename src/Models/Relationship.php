<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Relationship extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'core.relationships';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['id', 'from_party_id', 'type', 'to_party_id', 'created_by', 'created_at'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

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
            'from_party_id' => 'integer|required|exists:pgsql.core.parties,id',
            'to_party_id' => 'integer|required|exists:pgsql.core.parties,id',
            'type' => 'string|required|in:employee_of,friend_of,department_of,merchant_of'
        ];
    }

    /**
     * Creates a new relationship between two parties.
     * @param int $from_party_id From party ID
     * @param string $type employee_of|friend_of|department_of|merchant_of
     * @param int $to_party_id To party ID
     */
    public static function store($from_party_id, $type, $to_party_id)
    {
        // Build the attribute list.
        $attributes = [
            'from_party_id' => $from_party_id,
            'type' => $type,
            'to_party_id' => $to_party_id
        ];

        // Create the relationship.
        return self::create($attributes);
    }
}
