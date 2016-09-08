<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Charge extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'consumer.charges';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['order_id', 'status', 'payment_method', 'collector_party_id', 'deposit_id', 'total_amount', 'tendered_amount', 'change_amount', 'remarks', 'updated_by'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'order_id';

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [
            'order_id' => 'integer|required|exists:pgsql.consumer.orders,id',
            'status' => 'string|required|in:created,assigned,paid,remitted,paid_out',
            'payment_method' => 'string|required|in:' . implode(',', array_keys(config('settings.payment_methods'))),
            'collector_party_id' => 'integer|exists:pgsql.core.parties,id',
            'deposit_id' => 'integer|exists:pgsql.consumer.deposits,id',
            'total_amount' => 'numeric|required|min:0|max:999999999999.99',
            'tendered_amount' => 'numeric|required|min:0|max:999999999999.99',
            'change_amount' => 'numeric|required|min:0|max:999999999999.99',
            'remarks' => 'string',
            'updated_by' => 'integer|exists:pgsql.core.users,party_id'
        ];
    }

    /**
     * Creates a new order.
     */
    public static function store($order_id, $total_amount, $payment_method = null, $status = 'created', $tendered_amount = 0, $change_amount = 0, $remarks = null, $collector_party_id = null, $deposit_id = null, $updated_by = null)
    {
        try {
            // Build the attribute list.
            $attributes = [
                'order_id' => $order_id,
                'status' => $status,
                'payment_method' => $payment_method,
                'collector_party_id' => $collector_party_id,
                'deposit_id' => $deposit_id,
                'total_amount' => $total_amount,
                'tendered_amount' => $tendered_amount,
                'change_amount' => $change_amount,
                'remarks' => $remarks,
                'updated_by' => $updated_by
            ];

            // Create the charge. 
            return self::create($attributes);
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }
}
