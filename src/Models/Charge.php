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
            'status' => 'string|required|in:pending,assigned,paid,remitted,paid_out',
            'payment_method' => 'string|required|in:' . implode(',', array_keys(Order::PAYMENT['methods'])),
            'collector_party_id' => 'integer|nullable|exists:pgsql.core.parties,id',
            'deposit_id' => 'integer|nullable|exists:pgsql.consumer.deposits,id',
            'total_amount' => 'numeric|required|min:0|max:999999999999.99',
            'tendered_amount' => 'numeric|required|min:0|max:999999999999.99',
            'change_amount' => 'numeric|required|min:0|max:999999999999.99',
            'remarks' => 'string|nullable',
            'updated_by' => 'integer|nullable|exists:pgsql.core.users,party_id'
        ];
    }

    /**
     * A charge belongs to an order.
     */
    public function order()
    {
        return $this->belongsTo('F3\Models\Order');
    }

    /**
     * Sets the the charge status to "paid".
     */
    public function paid($tendered_amount, $change_amount = 0, $remarks = null)
    {
        // Check if the current status is the same as the new one.
        if ($this->status == 'delivered') {
            return $this;
        }

        // The tendered amount should be greater than the total amount.
        if ($tendered_amount < $this->total_amount) {
            throw new \Exception('Tendered amount should be greater than total amount.');
        }

        // Update the charge.
        $this->status = 'paid';
        $this->tendered_amount = $tendered_amount;
        $this->change_amount = $change_amount;
        $this->remarks = $remarks;
        $this->save();

        return $this;
    }

    /**
     * Creates a new order.
     */
    public static function store($order_id, $total_amount, $payment_method = null, $status = 'pending', $tendered_amount = 0, $change_amount = 0, $remarks = null, $collector_party_id = null, $deposit_id = null, $updated_by = null)
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
