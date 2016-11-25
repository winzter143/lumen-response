<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class BankAccount extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'core.bank_accounts';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['id', 'party_id', 'bank_id', 'currency_id', 'account_number', 'first_name', 'last_name', 'type', 'class', 'created_at', 'updated_at'];

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        // Set the validation rules.
        $rules = [
            'party_id' => 'integer|required|exists:pgsql.core.parties,id',
            'bank_id' => 'integer|required|exists:pgsql.core.banks,id',
            'currency_id' => 'integer|required|exists:pgsql.core.currencies,id',
            'first_name' => 'string|required|max:50',
            'last_name' => 'string|required|max:50',
            'last_name' => 'string|nullable|max:255',
            'type' => 'string|nullable|in:savings,current',
            'class' => 'string|nullable|in:personal,business',
        ];

        // Add the account number check if it's a new record.
        if (!$this->exists) {
            $rules['account_number'] = 'string|required|max:100|unique:pgsql.core.bank_accounts,account_number,NULL,id,bank_id,' . $this->bank_id;
        }

        return $rules;
    }

    /**
     * A bank account belongs to a bank.
     */
    public function bank()
    {
        return $this->belongsTo('F3\Models\Bank');
    }

    /**
     * A bank account has a currency.
     */
    public function currency()
    {
        return $this->belongsTo('F3\Models\Currency');
    }

    /**
     * A bank account belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo('F3\Models\User', 'party_id', 'party_id');
    }

    /**
     * A bank account belongs to an organization.
     */
    public function organization()
    {
        return $this->belongsTo('F3\Models\Organization', 'party_id', 'party_id');
    }

    /**
     * Creates a new bank account.
     */
    public static function store($party_id, $bank_id, $currency, $account_number, $first_name, $last_name, $branch = null, $type = null, $class = null)
    {
        try {
            // Look for the currency ID.
            $currency_id = DB::table('core.currencies')->where('code', $currency)->value('id');

            if (!$currency_id) {
                throw new \Exception('The provided currency code is not valid.', 422);
            }

            // Build the list of attributes to be saved.
            $attributes = [
                'party_id' => $party_id,
                'bank_id' => $bank_id,
                'currency_id' => $currency_id,
                'account_number' => $account_number,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'branch' => $branch,
                'type' => $type,
                'class' => $class,
            ];

            // Create the account.
            return self::create($attributes);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Updates the bank account.
     */
    public function updateAccount($bank_id, $currency, $account_number, $first_name, $last_name, $branch, $type, $class)
    {
        try {
            // Look for the currency ID.
            $currency_id = DB::table('core.currencies')->where('code', $currency)->value('id');

            if (!$currency_id) {
                throw new \Exception('The provided currency code is not valid.', 422);
            }

            // Update the account.
            $this->bank_id = $bank_id;
            $this->currency_id = $currency_id;
            $this->account_number = $account_number;
            $this->first_name = $first_name;
            $this->last_name = $last_name;
            $this->branch = $branch;
            $this->type = $type;
            $this->class = $class;
            $this->save();

            // Return the updated object.
            return $this;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
