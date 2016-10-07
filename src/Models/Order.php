<?php
namespace F3\Models;

use DB;
use F3\Components\Model;
use F3\Components\Courier;

class Order extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'consumer.orders';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['id', 'party_id', 'currency_id', 'reference_id', 'pickup_address_id', 'delivery_address_id', 'tracking_number', 'payment_method', 'payment_provider', 'status', 'buyer_name', 'email', 'contact_number', 'subtotal', 'shipping', 'tax', 'fee', 'grand_total', 'metadata', 'ip_address', 'preferred_pickup_time', 'preferred_delivery_time', 'insurance', 'insurance_fee', 'transaction_fee', 'shipping_fee', 'pickup_date', 'status_updated_at', 'active_segment_id', 'total_collected'];

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        // Set the validation rules.
        $rules = [
            'party_id' => 'integer|required|exists:pgsql.core.organizations,party_id',
            'currency_id' => 'integer|required|exists:pgsql.core.currencies,id',
            'pickup_address_id' => 'integer|required|exists:pgsql.core.addresses,id',
            'delivery_address_id' => 'integer|required|exists:pgsql.core.addresses,id',
            'tracking_number' => 'string|max:15',
            'payment_method' => 'string|required|in:' . implode(',', array_keys(config('settings.payment_methods'))),
            'payment_provider' => 'string|required|in:' . implode(',', array_keys(config('settings.payment_providers'))),
            'status' => 'string|in:' . implode(',', array_keys(config('settings.order_statuses'))),
            'buyer_name' => 'string|required|max:100',
            'email' => 'string|email|max:50|required_without:contact_number',
            'contact_number' => 'string|max:50|required_without:email',
            'subtotal' => 'numeric|required|min:0|max:999999999999.99',
            'shipping' => 'numeric|nullable|min:0|max:999999999999.99',
            'tax' => 'numeric|nullable|min:0|max:999999999999.99',
            'fee' => 'numeric|nullable|min:0|max:999999999999.99',
            'insurance' => 'numeric|nullable|min:0|max:999999999999.99',
            'grand_total' => 'numeric|required|min:0|max:999999999999.99',
            'metadata' => 'json|nullable',
            'ip_address' => 'ip|nullable',
            'preferred_pickup_time' => 'string|nullable|max:100',
            'preferred_delivery_time' => 'string|nullable|max:100',
            'flagged' => 'integer|in:0,1'
        ];

        // Add the reference ID check if it's a new record.
        if (!$this->exists) {
            $rules['reference_id'] = 'string|required|max:100|unique:pgsql.consumer.orders,reference_id,NULL,id,party_id,' . $this->party_id;
        }

        return $rules;
    }

    /**
     * Returns the next ID in the sequence.
     */
    public static function getNextId()
    {
        return DB::select('select nextval(:sequence)', ['sequence' => 'consumer.orders_id_seq'])[0]['nextval'];
    }

    /**
     * Creates a new order.
     */
    public static function store($party_id, $pickup_address, $delivery_address, $buyer_name, $email, $contact_number, $grand_total, $payment_method, $payment_provider, $reference_id, $total_collected = 0, $items = [], $currency = 'PHP', $metadata = null, $preferred_pickup_time = null, $preferred_delivery_time = null, $ip_address = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Look for the currency ID.
            $currency_id = DB::table('core.currencies')->where('code', $currency)->value('id');

            if (!$currency_id) {
                throw new \Exception('The provided currency code is not valid.', 422);
            }

            // Create the source address.
            $pickup_address = Address::store($party_id, 'pickup', array_get($pickup_address, 'name'), array_get($pickup_address, 'line_1'), array_get($pickup_address, 'line_2'), array_get($pickup_address, 'city'), array_get($pickup_address, 'state'), array_get($pickup_address, 'postal_code'), array_get($pickup_address, 'country'), array_get($pickup_address, 'remarks'), array_get($pickup_address, 'created_by'), array_get($pickup_address, 'title'), array_get($pickup_address, 'email'), array_get($pickup_address, 'phone_number'), array_get($pickup_address, 'mobile_number'), array_get($pickup_address, 'fax_number'), array_get($pickup_address, 'company'));

            // Create the destination address.
            $delivery_address = Address::store($party_id, 'delivery', array_get($delivery_address, 'name'), array_get($delivery_address, 'line_1'), array_get($delivery_address, 'line_2'), array_get($delivery_address, 'city'), array_get($delivery_address, 'state'), array_get($delivery_address, 'postal_code'), array_get($delivery_address, 'country'), array_get($delivery_address, 'remarks'), array_get($delivery_address, 'created_by'), array_get($delivery_address, 'title'), array_get($delivery_address, 'email'), array_get($delivery_address, 'phone_number'), array_get($delivery_address, 'mobile_number'), array_get($delivery_address, 'fax_number'), array_get($delivery_address, 'company'));

            // Round off the grand total to two decimal places.
            $grand_total = round($grand_total, 2);

            // Compute for the fees.
            $fees = self::getFees($party_id, $grand_total, $delivery_address, $payment_method);

            // Get the breakdown of the total amount.
            // This will also check if grand total is the same as the item total.
            $breakdown = self::getTotalBreakdown($items, $grand_total);

            // Get an order ID and tracking number.
            $order_id = self::getNextId();
            $tracking_number = self::getTrackingNumber($order_id);

            // Encode the metadata.
            $metadata = ($metadata) ? json_encode($metadata) : null;

            // Build the list of attributes to be saved.
            $attributes = array_merge($breakdown, $fees, [
                'id' => $order_id,
                'tracking_number' => $tracking_number,
                'pickup_address_id' => $pickup_address->id,
                'delivery_address_id' => $delivery_address->id,
                'party_id' => $party_id,
                'currency_id' => $currency_id,
                'pickup_address' => $pickup_address,
                'delivery' => $delivery_address,
                'buyer_name' => $buyer_name,
                'email' => $email,
                'contact_number' => $contact_number,
                'grand_total' => $grand_total,
                'payment_method' => $payment_method,
                'payment_provider' => $payment_provider,
                'reference_id' => $reference_id,
                'metadata' => $metadata,
                'preferred_pickup_time' => $preferred_pickup_time,
                'preferred_delivery_time' => $preferred_delivery_time,
                'ip_address' => $ip_address
            ]);

            // Create the order.
            $order = self::create($attributes);

            // Create the order items.
            if (is_array($items) && $items) {
                // Check if a product item is present.
                if (!in_array('product', array_values(array_column($items, 'type')))) {
                    throw new \Exception('At least one order item of type "product" is required.', 422);
                }

                // Create the order items.
                foreach ($items as $item) {
                    $order_items[] = $order->addItem(array_get($item, 'type'), array_get($item, 'description'), array_get($item, 'amount'), array_get($item, 'quantity'), array_get($item, 'metadata'));
                }
            } else {
                // There are no items.
                throw new \Exception('At least one order item of type "product" is required.', 422);
            }

            // Create a charge if the COD flag is set.
            if ($order->payment_method == 'cod') {
                $charge = $order->createCharge();
            } else {
                $charge = null;
            }

            try {
                // Create the route plan.
                $routes = Courier::ship($order->getAttributes(), $pickup_address->getAttributes(), $delivery_address->getAttributes());

                // We're unable to come up with a route plan.
                // Accept the order but flag it.
                if (!$routes) {
                    throw new \Exception('Unable to determine route plan.');
                }

                // Add the routes/segments to the order.
                // Create the order segments.
                $order_segments = [];
                foreach ($routes as $k => $route) {
                    $order_segments[$k] = $order->addSegment($route['courier_party_id'], $route['type'], $route['shipping_type'], $route['reference_id'], $route['barcode_format'], $route['pickup_address_id'], $route['delivery_address_id'], $route['start_date'], $route['end_date'], $route['currency_id'], $route['amount']);
                    $order_segments[$k]['courier'] = $route['courier'];
                    $order_segments[$k]['type'] = $route['type'];
                    $order_segments[$k]['barcode_format'] = $route['barcode_format'];
                    $order_segments[$k]['currency'] = $route['currency'];
                    $order_segments[$k]['active'] = ($k == 0) ? true : false;
                    $order_segments[$k]['pickup_address'] = $route['pickup_address'];
                    $order_segments[$k]['delivery_address'] = $route['delivery_address'];
                }

                // Set the active segment to be the first route.
                $order->setActiveSegment($order_segments[0]->id);

                // Log the pending status.
                $order->addEvent('pending');

                // The order is ready for pick up.
                $order->forPickup();
            } catch (\Exception $e) {
                $order_segments = [];
                if (isset($e->validator)) {
                    // There was a validation error.
                    throw $e;
                } else {
                    // Flag the order.
                    $order->flag();
                }
            }

            // Commit and return the order.
            DB::commit();
            return [
                'order' => $order,
                'pickup_address' => $pickup_address,
                'delivery_address' => $delivery_address,
                'order_items' => $order_items,
                'charge' => $charge,
                'order_segments' => $order_segments
            ];
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Validates the the shipping orders and assigns a tracking number for asynchronous processing.
     */
    public static function prepare($party_id, $order, $currencies, $countries)
    {
        // Add the organization ID to the order.
        $order['party_id'] = $party_id;

        // Determine the currency ID.
        $order['currency_id'] = array_get($currencies, array_get($order, 'currency', config('settings.defaults.currency')));

        if (!$order['currency_id']) {
            throw new \Exception('The provided currency code is not valid.', 422);
        }

        // Determine the country ID of the pickup address.
        $order['pickup_address']['location_id'] = array_get($countries, array_get($order, 'pickup_address.country'));

        if (!$order['pickup_address']['location_id']) {
            throw new \Exception('The provided country code ' . array_get($order, 'pickup_address.country') . ' for the pickup address is not valid.', 422);
        }

        // Determine the country ID of the delivery address.
        $order['delivery_address']['location_id'] = array_get($countries, array_get($order, 'delivery_address.country'));

        if (!$order['delivery_address']['location_id']) {
            throw new \Exception('The provided country code ' .  array_get($order, 'delivery_address.country'). ' for the delivery address is not valid.', 422);
        }

        // Hash the addresses.
        $order['pickup_address']['hash'] = Address::hash($order['pickup_address']);
        $order['delivery_address']['hash'] = Address::hash($order['delivery_address']);

        // Round off the amount.
        $order['amount'] = round($order['amount'], 2);

        // Get the total breakdown and merge it with the order.
        $order = array_merge($order, self::getTotalBreakDown(array_get($order, 'items'), $order['amount']));

        // Encode the metadata.
        $order['metadata'] = array_get($order, 'metadata');
        $order['metadata'] = ($order['metadata']) ? json_encode($order['metadata']) : null;

        // Validate the order.
        $model = new self($order);
        $rules = array_except($model->getRules(), ['party_id', 'currency_id', 'pickup_address_id', 'delivery_address_id']);
        $model->validate(null, $rules);

        // Decode the metadata.
        $order['metadata'] = json_decode($order['metadata']);

        // Validate the pickup address.
        $model = new Address;
        $rules = array_except($model->getRules(), ['party_id', 'location_id']);
        $model->fill($order['pickup_address'])->validate(null, $rules);
        $model->fill($order['delivery_address'])->validate(null, $rules);

        // Validate the order items.
        if (isset($order['items']) && $order['items']) {
            foreach ($order['items'] as $k => $item) {
                // Encode the metadata.
                $item['metadata'] = array_get($item, 'metadata');
                $item['metadata'] = ($item['metadata']) ? json_encode($item['metadata']) : null;

                // Validate the item.
                $model = new OrderItem($item);
                $rules = array_except($model->getRules(), ['order_id', 'total']);
                $model->validate(null, $rules);
            }
        }

        // Generate an order ID and tracking number.
        $order['id'] = self::getNextId();
        $order['tracking_number'] = self::getTrackingNumber($order['id']);
        return $order;
    }

    /**
     * Creates a new charge.
     */
    public function createCharge()
    {
        return Charge::store($this->id, $this->grand_total, $this->payment_method);
    }

    /**
     * Adds an order item to this order.
     */
    public function addItem($type, $description, $amount, $quantity, $metadata)
    {
        return OrderItem::store($this->id, $type, $description, $amount, $quantity, $metadata);
    }

    /**
     * Adds a segment/route to this order.
     */
    public function addSegment($courier_party_id, $type, $shipping_type, $reference_id, $barcode_format, $pickup_address_id, $delivery_address_id, $start_date = null, $end_date = null, $currency_id = null, $amount = null, $status = 'pending', $flagged = 0)
    {
        return OrderSegment::store($this->id, $courier_party_id, $type, $shipping_type, $reference_id, $barcode_format, $pickup_address_id, $delivery_address_id, $start_date, $end_date, $currency_id, $amount, $status, $flagged);
    }

    /**
     * Adds an order event.
     */
    public function addEvent($status, $remarks = null)
    {
        return OrderEvent::store($this->active_segment_id, $status, $remarks);
    }

    /**
     * Sets the the active segment/route of this order.
     */
    public function setActiveSegment($segment_id)
    {
        // Update the active segment.
        $this->active_segment_id = $segment_id;
        return $this->save();
    }

    /**
     * Flags the order.
     */
    public function flag()
    {
        // Update the flag value.
        $this->flagged = 1;
        return $this->save();
    }

    /**
     * Sets the the order status.
     */
    public function setStatus($status, $remarks = null)
    {
        try {
            // Check if the current status is the same as the new one.
            if ($this->status == $status) {
                return $this;
            }

            // Start the transaction.
            DB::beginTransaction();

            // Update the turnaround time.
            $tat = json_decode($this->tat, true);
            $tat[$status] = date('r');
            $tat = json_encode($tat);
            $this->tat = $tat;

            // Update the order status.
            $this->status = $status;
            $this->status_updated_at = DB::raw('now()');
            $this->save();

            // Create an order event.
            $this->addEvent($status, $remarks);

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
     * Updates the order status to "picked_up" and sets the pickup date.
     */
    public function pickedUp($pickup_date, $remarks = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Set the status.
            $this->setStatus('picked_up', $remarks);

            // Look for the next segment.
            $next_segment = DB::table('consumer.order_segments')->where([['order_id', $this->id], ['id', '>', $this->active_segment_id]])->limit(1)->first();

            if ($next_segment) {
                // Update the active segment.
                $this->setActiveSegment($next_segment['id']);
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
     * Updates the order status to "for_pickup" and increments "pickup_attempts".
     */
    public function forPickup($remarks = null, $increment = true)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Set the status.
            $this->setStatus('for_pickup', $remarks);

            // Increment pickup_attempts.
            if ($increment) {
                $this->pickup_attempts = DB::raw('pickup_attempts + 1');
                $this->save();
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
     * Updates the order status to "in_transit".
     */
    public function inTransit($remarks = null, $increment = true)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Set the status.
            $this->setStatus('in_transit', $remarks);

            // Increment delivery_attempts.
            if ($increment) {
                $this->delivery_attempts = DB::raw('delivery_attempts + 1');
                $this->save();
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
     * Sets the order status to "pending".
     */
    public function pending($remarks = null)
    {
        return $this->setStatus('pending', $remarks);
    }

    /**
     * Sets the order status to "failed_pickup".
     */
    public function failedPickup($remarks = null)
    {
        return $this->setStatus('failed_pickup', $remarks);
    }

    /**
     * Sets the order status to "claimed".
     */
    public function claimed($remarks = null)
    {
        return $this->setStatus('claimed', $remarks);
    }

    /**
     * Sets the order status to "delivered".
     */
    public function delivered($remarks = null)
    {
        return $this->setStatus('delivered', $remarks);
    }

    /**
     * Sets the order status to "return_in_transit".
     */
    public function returnInTransit($remarks = null)
    {
        return $this->setStatus('return_in_transit', $remarks);
    }

    /**
     * Sets the order status to "returned".
     */
    public function returned($remarks = null)
    {
        return $this->setStatus('returned', $remarks);
    }

    /**
     * Sets the order status to "failed_return".
     */
    public function failedReturn($remarks = null)
    {
        return $this->setStatus('failed_return', $remarks);
    }

    /**
     * Claims an order.
     */
    public function claim($amount, $reason, $documentary_proof_url = null, $shipping_fee_flag = 0, $insurance_fee_flag = 0, $transaction_fee_flag = 0, $status = 'pending')
    {
        return Claim::store($this->id, $amount, $reason, $documentary_proof_url, $shipping_fee_flag, $insurance_fee_flag, $transaction_fee_flag, $status);
    }

    /**
     * Checks if a pickup can be retried based on the client's contract.
     * The order status is set to "failed_pickup" if the retry is not allowed.
     */
    public function retryPickup($remarks = null)
    {
        // Get the default contract.
        $default = config('settings.defaults.contract');

        // Get the client contract.
        $contract = Party::getMetaData($this->party_id, 'contract');

        // Set the default values.
        $contract = is_array($contract) ? array_merge($default, $contract) : $default;

        if (is_null($contract['pickup_retries'])) {
            // There is no limit.
            return $this->forPickup($remarks);
        } else {
            if ($this->pickup_attempts < $contract['pickup_retries']) {
                // The order can still be processed..
                return $this->forPickup($remarks);
            } else {
                // Set the order status to failed pickup.
                return $this->setStatus('failed_pickup', $remarks);
            }
        }
    }

    /**
     * Generates a transaction number from the given ID.
     * @param int $id
     */
    public static function getTrackingNumber($id)
    {
        // Generate the transaction number, in format XXXX-XXXX-XXXX (eg, 0000-0297-ACLZ)
        // Note we exclude I and O in the list so as not to confuse them with 1 and 0.
        return implode("-", str_split(str_pad($id, 8, "0", STR_PAD_LEFT) . substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ"), 0, 4), 4));
    }

    /**
     * Computes for the grand total of the order items.
     */
    public static function getTotalBreakdown($items, $grand_total)
    {
        // Set the default values.
        $breakdown = ['shipping' => null, 'tax' => null, 'fee' => null, 'insurance' => null, 'subtotal' => 0, 'grand_total' => 0];

        if (is_array($items) && $items) {
            // Loop through each item and compute for the total.
            foreach ($items as $item) {
                // Get the item type.
                $item_type = array_get($item, 'type');

                switch ($item_type) {
                case 'product':
                    $breakdown['subtotal'] += round(array_get($item, 'amount') * array_get($item, 'quantity'), 2);
                    break;
                case 'shipping':
                case 'tax':
                case 'fee':
                case 'insurance':
                    $breakdown[$item_type] += round(array_get($item, 'amount'), 2);
                    break;
                default:
                    throw new \Exception("Order item type '{$item_type}' is not valid.", 422);
                }
            }
        } else {
            // There are no order items.
            // Set the subtotal to be the grand total.
            $breakdown['subtotal'] = $grand_total;
        }

        // Compute for the grand total.
        $breakdown['grand_total'] = round($breakdown['subtotal'] + $breakdown['shipping'] + $breakdown['tax'] + $breakdown['fee'] + $breakdown['insurance'], 2);

        // Check if the grand total value is the same as the item total.
        if ($grand_total == $breakdown['grand_total']) {
            return $breakdown;
        } else {
            throw new \Exception('Grand total does not match the order item total.', 422);
        }
    }

    /**
     * Computes for the client fees.
     */
    public static function getFees($party_id, $grand_total, $delivery_address, $payment_method)
    {
        // Get the default contract.
        $default = config('settings.defaults.contract');

        // Fetch the client contract.
        $contract = Party::getMetaData($party_id, 'contract');
        $contract = __is_array_associative($contract) ? array_merge($default, $contract) : $default;

        // Determine the shipping fee.
        $fees['shipping_fee'] = Address::isProvincial($delivery_address->getAttributes()) ? $contract['shipping_fee']['provincial'] : $contract['shipping_fee']['manila'];

        // Determine the insurance fee.
        $fees['insurance_fee'] = ($contract['insurance_fee']['type'] == 'percent') ? round($contract['insurance_fee']['value'] * $grand_total, 2) : $contract['insurance_fee']['value'];
        $fees['insurance_fee'] = max($fees['insurance_fee'], $contract['insurance_fee']['max']);

        // Determine the service fee.
        if ($payment_method == 'cod') {
            $fees['transaction_fee'] = ($contract['transaction_fee']['type'] == 'percent') ? round($contract['transaction_fee']['value'] * $grand_total, 2) : $contract['transaction_fee']['value'];
            $fees['transaction_fee'] = max($fees['transaction_fee'], $contract['transaction_fee']['max']);
        } else {
            $fees['transaction_fee'] = 0;
        }

        return $fees;
    }
}
