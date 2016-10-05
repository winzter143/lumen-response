<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Claim extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'consumer.claims';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['order_id', 'status', 'reason', 'amount', 'shipping_fee_flag', 'insurance_fee_flag', 'transaction_fee_flag', 'documentary_proof_url', 'remarks', 'created_at', 'created_by', 'updated_at', 'updated_by'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'order_id';

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        // Set the validation rules.
        $rules = [
            'order_id' => 'integer|required|exists:pgsql.consumer.orders,id',
            'status' => 'string|in:pending,verified,settled,declined',
            'reason' => 'string|required',
            'amount' => 'numeric|required|min:0|max:999999999999.99',
            'shipping_fee_flag' => 'integer|in:0,1',
            'insurance_fee_flag' => 'integer|in:0,1',
            'transaction_fee_flag' => 'integer|in:0,1',
            'documentary_proof_url' => 'url|nullable',
            'remarks' => 'string|nullable'
        ];

        return $rules;
    }

    /**
     * Creates a new order.
     */
    public static function store($order_id, $amount, $reason, $documentary_proof_url = null, $shipping_fee_flag = 0, $insurance_fee_flag = 0, $transaction_fee_flag = 0, $remarks = null, $status = 'pending')
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Perform additional checks.
            // Fetch the order and merchant contract.
            $order = DB::table('consumer.orders as o')->select(['o.delivery_date', 'o.status', 'o.grand_total', 'p.metadata'])->join('core.parties as p', 'p.id', '=', 'o.org_party_id')->where([['o.id', $order_id], ['p.status', 1]])->first();

            if (!$order) {
                throw new \Exception('The order does not exist.', 422);
            }

            // Check if there are existing claims for the order.
            $claim = self::where('order_id', $order_id)->first();

            if ($claim) {
                throw new \Exception('This order has already been claimed.', 422);
            }

            // Get the contract.
            $contract = json_decode($order['metadata'], true);

            // Get the claim period from the contract.
            $claim_period = array_get($contract, 'claim_period', config('settings.defaults.contract.claim_period'));

            // The order status should be "delivered".
            if ($order['status'] != 'delivered') {
                throw new \Exception('The order has not yet been delivered.', 422);
            }

            // The order status should be "delivered".
            if (round((time() - strtotime($order['delivery_date'])) / 60 / 60 / 24) > $claim_period) {
                throw new \Exception('The order can only be claimed within ' . $claim_period . ' days of delivery.', 422);
            }

            // The claim amount should not exceed the order total.
            if ($amount > $order['grand_total']) {
                throw new \Exception('The claim amount should not exceed the total order amount.', 422);
            }

            // Build the list of attributes to be saved.
            $attributes = [
                'order_id' => $order_id,
                'amount' => $amount,
                'reason' => $reason,
                'documentary_proof_url' => $documentary_proof_url,
                'shipping_fee_flag' => $shipping_fee_flag,
                'insurance_fee_flag' => $insurance_fee_flag,
                'transaction_fee_flag' => $transaction_fee_flag,
                'remarks' => $remarks,
                'status' => $status,
            ];

            // Create the claim.
            $claim = self::create($attributes);

            // Commit and return the order.
            DB::commit();
            return $claim;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }
}
