<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Currency extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'core.currencies';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['code', 'name', 'symbol'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * A charge belongs to an order.
     */
    public function order()
    {
        return $this->belongsTo('F3\Models\Order');
    }
}
