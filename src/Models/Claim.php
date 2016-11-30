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
    protected $fillable = ['order_id', 'status', 'reason', 'amount', 'shipping_fee_flag', 'insurance_fee_flag', 'transaction_fee_flag', 'assets', 'remarks', 'created_at', 'created_by', 'updated_at', 'updated_by', 'request_number', 'tat', 'reference_id'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'order_id';

    /**
     * A claim belongs to an order.
     */
    public function order()
    {
        return $this->belongsTo('F3\Models\Order');
    }

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        // Set the validation rules.
        $rules = [
            'order_id' => 'integer|required|exists:pgsql.consumer.orders,id',
            'request_number' => 'string|required|max:15',
            'reference_id' => 'string|nullable|max:100',
            'status' => 'string|in:pending,verified,settled,declined',
            'reason' => 'string|required',
            'amount' => 'numeric|required|min:0|max:999999999999.99',
            'shipping_fee_flag' => 'integer|in:0,1',
            'insurance_fee_flag' => 'integer|in:0,1',
            'transaction_fee_flag' => 'integer|in:0,1',
            'assets' => 'json|nullable',
            'remarks' => 'string|nullable'
        ];

        return $rules;
    }

    /**
     * Creates a new order.
     * @param int $order_id Order ID
     * @param float $amount Amount to be claimed
     * @param string $reason Claim reason
     * @param array $assets Array of assets/images
     * @param int $shipping_fee_flag Set to 1 to refund shipping fee, set to 0 otherwise
     * @param int $insurance_fee_flag Set to 1 to refund insurance fee, set to 0 otherwise
     * @param int $transaction_fee_flag Set to 1 to refund transaction fee, set to 0 otherwise
     * @param string $remarks Miscellaneous remarks
     * @param string $status Claim status
     * @param string $reference_id Reference ID / credit memo #
     * @param array $created_by User details
     */
    public static function store($order_id, $amount, $reason, $assets = null, $shipping_fee_flag = 0, $insurance_fee_flag = 0, $transaction_fee_flag = 0, $remarks = null, $status = 'pending', $reference_id = null, array $created_by = [])
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Perform additional checks.
            // Fetch the order and merchant contract.
            $order = DB::table('consumer.orders as o')->select(['o.tat', 'o.status', 'o.grand_total', 'p.metadata'])->join('core.parties as p', 'p.id', '=', 'o.party_id')->where([['o.id', $order_id], ['p.status', 1]])->first();

            if (!$order) {
                throw new \Exception('The order does not exist.', 422);
            }

            // Check if there are existing claims for the order.
            $claim = self::where('order_id', $order_id)->first();

            if ($claim) {
                throw new \Exception('This order has already been claimed.', 422);
            }

            // Get the contract and turnaround time.
            $contract = json_decode($order['metadata'], true);
            $tat = json_decode($order['tat'], true);

            // Get the claim period from the contract.
            $claim_period = array_get($contract, 'claim_period', config('settings.defaults.contract.claim_period'));

            // The order status should be "delivered" and the "delivered" timestamp should be present in $tat.
            if ($order['status'] != 'delivered' || !isset($tat['delivered'])) {
                throw new \Exception('The order has not yet been delivered.', 422);
            }

            // The order status should be "delivered".
            if (round((time() - strtotime($tat['delivered'])) / 60 / 60 / 24) > $claim_period) {
                throw new \Exception('The order can only be claimed within ' . $claim_period . ' days of delivery.', 422);
            }

            // The claim amount should not exceed the order total.
            if ($amount > $order['grand_total']) {
                throw new \Exception('The claim amount should not exceed the total order amount.', 422);
            }

            // Set the tat.
            $tat = json_encode([
                'pending' => array_merge($created_by, ['date' => date('r')])
            ]);

            // Build the list of attributes to be saved.
            $attributes = [
                'order_id' => $order_id,
                'request_number' => self::getRequestNumber($order_id),
                'reference_id' => $reference_id,
                'amount' => $amount,
                'reason' => $reason,
                'assets' => json_encode($assets),
                'shipping_fee_flag' => $shipping_fee_flag,
                'insurance_fee_flag' => $insurance_fee_flag,
                'transaction_fee_flag' => $transaction_fee_flag,
                'remarks' => $remarks,
                'status' => $status,
                'tat' => $tat
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

    /**
     * Sets the the claim status.
     */
    public function setStatus($status, $remarks = null, $update_tat = true)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Check if the current status is the same as the new one.
            if ($this->status == $status) {
                return $this;
            }

            // Update the order status.
            $this->status = $status;
            $this->remarks = $remarks;
            $this->save();

            if ($update_tat) {
                $this->updateTat($status);
            }

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
     * Sets the order turnaround time for the given status.
     */
    public function updateTat($status, $date = null)
    {
        // Update the turnaround time.
        $tat = json_decode($this->tat, true);
        $tat[$status] = ($date) ? date('r', strtotime($date)) : date('r');
        $tat = json_encode($tat);
        $this->tat = $tat;
        return $this->save();
    }

    /**
     * Sets the claim status to "pending".
     */
    public function pending($remarks = null)
    {
        return $this->setStatus('pending', $remarks);
    }

    /**
     * Sets the claim status to "verified".
     */
    public function verified($ip_address = null, $remarks = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Update the status.
            $this->setStatus('verified');

            // Determine the amount to be transferred back to the client.
            $amount = $this->amount;

            if ($this->shipping_fee_flag) {
                $amount += $this->order->shipping_fee;
            }

            if ($this->insurance_fee_flag) {
                $amount += $this->order->insurance_fee;
            }

            if ($this->transaction_fee_flag) {
                $amount += $this->order->transaction_fee;
            }

            // Transfer the claim amount, shipping, insurance, and transaction fees from the system's sales wallet to the client's fund wallet.
            $details = 'Claim for order #' . $this->order->tracking_number;
            Wallet::transfer(config('settings.system_party_id'), $this->order->party_id, 'sales', 'fund', $this->order->currency->code, $amount, 'fund', $details, $this->order_id, $ip_address);

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
     * Sets the claim status to "settled".
     */
    public function settled($reference_id, $remarks = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Set the reference ID.
            $this->reference_id = $reference_id;

            // Update the status.
            $this->setStatus('settled', $remarks);

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
     * Sets the claim status to "declined".
     */
    public function declined($remarks)
    {
        return $this->setStatus('declined', $remarks);
    }

    /**
     * Generates a request number from the given order ID.
     * @param int $order_id
     */
    public static function getRequestNumber($order_id)
    {
        // Generate the request number, in format XXXX-XXXX (eg, 0000-0001-ACLZ)
        // Note we exclude I and O in the list so as not to confuse them with 1 and 0.
        return implode("-", str_split(str_pad($order_id, 8, "0", STR_PAD_LEFT) . substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ"), 0, 4), 4));
    }
}
