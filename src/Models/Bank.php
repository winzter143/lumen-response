<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Bank extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'core.banks';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['id', 'name', 'swift_code', 'phone_number', 'created_at'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Turn off timestamps. This will prevent Laravel from updating created_at and updated_at.
     */
    public $timestamps = false;

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [
            'name' => 'string|required|max:255|unique:pgsql.core.banks,name',
            'swift_code' => 'string|nullable|max:50',
            'phone_number' => 'string|nullable|max:50',
        ];
    }

    /**
     * A bank has many accounts.
     */
    public function bankAccounts()
    {
        return $this->hasMany('F3\Models\BankAccount');
    }
}
