<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Location extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'core.locations';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['code', 'name', 'type', 'parent_id', 'postal_code', 'status'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * A location belongs to an address.
     */
    public function address()
    {
        return $this->belongsTo('F3\Models\Address');
    }
}
