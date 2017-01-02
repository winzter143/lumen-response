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
            'status' => 'string|required|in:pending,assigned,paid,remitted,paid_out,refunded',
            'payment_method' => 'string|required|in:' . implode(',', array_keys(config('settings.payment_methods'))),
            'collector_party_id' => 'integer|nullable|exists:pgsql.core.parties,id',
            'deposit_id' => 'integer|nullable|exists:pgsql.consumer.deposits,id',
            'total_amount' => 'numeric|required|min:0|max:999999999999.99',
            'tendered_amount' => 'numeric|required|min:0|max:999999999999.99',
            'change_amount' => 'numeric|required|min:0|max:999999999999.99',
            'remarks' => 'string|nullable',
            'reference_id' => 'string|nullable|max:100',
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
     * Sets the the charge status to "refunded".
     */
    public function refunded($amount, $ip_address)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            if ($this->status != 'paid') {
                throw new Exception('The order has not been paid for yet.', 422);
            }

            // Update the status.
            $this->status = 'refunded';
            $this->save();

            // Transfer the claim amount to the client.
            $this->transferClaim($amount, $ip_address);

            // Commit.
            DB::commit();
            return $this;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
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

    /**
     * Transfers the total order amount from the system's collection wallet to the client's fund wallet.
     * @param string $ip_address Client's IP address
     */
    public function transferFunds($ip_address)
    {
        // Check if the order has been paid for.
        if (!$this->status == 'paid') {
            throw new \Exception('The order has not yet been paid for.', 422);
        }

        // Add the tracking number to the description.
        $details = 'Funds for COD order #' . $this->order->tracking_number;

        // Transfer the total from the system's collection wallet to the client's fund wallet.
        return Wallet::transfer(config('settings.system_party_id'), $this->order->party_id, 'collections', 'fund', $this->order->currency->code, $this->total_amount, 'fund', $details, $this->order_id, $ip_address);
    }

    /**
     * Transfers the claim amount, shipping, insurance, and transaction fees from the system's sales wallet to the client's fund wallet.
     * @param string $ip_address Client's IP address
     * @param bool $shipping_fee_flag Shipping fee flag. If set to true, shipping fee will be transferred back to the client.
     * @param bool $insurance_fee_flag Insurance fee flag. If set to true, insurance fee will be transferred back to the client.
     * @param bool $transaction_fee_flag Transaction fee flag. If set to true, transaction fee will be transferred back to the client.
     */
    public function transferClaim($amount, $ip_address)
    {
        // Add the tracking number to the description.
        $details = 'Claim for order #' . $this->order->tracking_number;
        return Wallet::transfer(config('settings.system_party_id'), $this->order->party_id, 'sales', 'fund', $this->order->currency->code, $amount, 'claim', $details, $this->order_id, $ip_address);
    }
}
