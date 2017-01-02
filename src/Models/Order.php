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
    protected $fillable = ['id', 'party_id', 'currency_id', 'reference_id', 'pickup_address_id', 'delivery_address_id', 'tracking_number', 'payment_method', 'payment_provider', 'status', 'buyer_name', 'email', 'contact_number', 'subtotal', 'shipping', 'tax', 'fee', 'grand_total', 'metadata', 'ip_address', 'preferred_pickup_time', 'preferred_delivery_time', 'insurance', 'insurance_fee', 'transaction_fee', 'shipping_fee', 'pickup_date', 'status_updated_at', 'active_segment_id', 'total_collected', 'match_status', 'remarks'];

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        // Set the validation rules.
        $rules = [
            'party_id' => 'integer|required|exists:pgsql.core.organizations,party_id',
            'currency_id' => 'integer|required|exists:pgsql.core.currencies,id',
            'pickup_address_id' => 'integer|nullable|exists:pgsql.core.addresses,id',
            'delivery_address_id' => 'integer|required|exists:pgsql.core.addresses,id',
            'tracking_number' => 'string|max:15',
            'payment_method' => 'string|required|in:' . implode(',', array_keys(config('settings.payment_methods'))),
            'payment_provider' => 'string|required|in:' . implode(',', array_keys(config('settings.payment_providers'))),
            'status' => 'string|in:' . implode(',', array_keys(config('settings.order_statuses'))),
            'match_status' => 'string|nullable|in:' . implode(',', array_keys(config('settings.match_statuses'))),
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
            'remarks' => 'string|nullable',
            'flagged' => 'integer|in:0,1'
        ];

        // Add the reference ID check if it's a new record.
        if (!$this->exists) {
            $rules['reference_id'] = 'string|required|max:100|unique:pgsql.consumer.orders,reference_id,NULL,id,party_id,' . $this->party_id;
        }

        return $rules;
    }

    /**
     * An order has a charge.
     */
    public function charge()
    {
        return $this->hasOne('F3\Models\Charge');
    }

    /**
     * An order has a claim.
     */
    public function claim()
    {
        return $this->hasOne('F3\Models\Claim');
    }

    /**
     * An order has a currency.
     */
    public function currency()
    {
        return $this->hasOne('F3\Models\Currency', 'id', 'currency_id');
    }

    /**
     * An order has a pickup address.
     */
    public function pickupAddress()
    {
        return $this->hasOne('F3\Models\Address', 'id', 'pickup_address_id');
    }

    /**
     * An order has a delivery address.
     */
    public function deliveryAddress()
    {
        return $this->hasOne('F3\Models\Address', 'id', 'delivery_address_id');
    }

    /**
     * An order has many items.
     */
    public function items()
    {
        return $this->hasMany('F3\Models\OrderItem');
    }

    /**
     * An order belongs to an organization.
     */
    public function organization()
    {
        return $this->belongsTo('F3\Models\Organization', 'party_id', 'party_id');
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
    public static function store($party_id, $pickup_address, $delivery_address, $buyer_name, $email, $contact_number, $grand_total, $payment_method, $payment_provider, $reference_id, $total_collected = 0, $items = [], $currency = 'PHP', $status = 'pending', $metadata = null, $preferred_pickup_time = null, $preferred_delivery_time = null, $ip_address = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Get the party contract.
            $contract = Party::getContract($party_id);

            // Look for the currency ID.
            $currency_id = DB::table('core.currencies')->where('code', $currency)->value('id');

            if (!$currency_id) {
                throw new \Exception('The provided currency code is not valid.', 422);
            }

            // Create the pick up address if it's passed.
            if ($pickup_address) {
                $pickup_address = Address::store($party_id, 'pickup', array_get($pickup_address, 'name'), array_get($pickup_address, 'line_1'), array_get($pickup_address, 'line_2'), array_get($pickup_address, 'city'), array_get($pickup_address, 'state'), array_get($pickup_address, 'postal_code'), array_get($pickup_address, 'country'), array_get($pickup_address, 'remarks'), array_get($pickup_address, 'created_by'), array_get($pickup_address, 'title'), array_get($pickup_address, 'email'), array_get($pickup_address, 'phone_number'), array_get($pickup_address, 'mobile_number'), array_get($pickup_address, 'fax_number'), array_get($pickup_address, 'company'));
            }

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
                'pickup_address_id' => ($pickup_address) ? $pickup_address->id : null,
                'delivery_address_id' => $delivery_address->id,
                'party_id' => $party_id,
                'currency_id' => $currency_id,
                'delivery' => $delivery_address,
                'buyer_name' => $buyer_name,
                'email' => $email,
                'contact_number' => $contact_number,
                'grand_total' => $grand_total,
                'payment_method' => $payment_method,
                'payment_provider' => $payment_provider,
                'reference_id' => $reference_id,
                'status' => $status,
                'metadata' => $metadata,
                'preferred_pickup_time' => $preferred_pickup_time,
                'preferred_delivery_time' => $preferred_delivery_time,
                'ip_address' => $ip_address,
                'status_updated_at' => DB::raw('now()')
            ]);

            // Create the order.
            $order = self::create($attributes);

            // The order has been created. Log the pending status.
            $order->addEvent('pending');

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

            // Create the charge object.
            if ($contract['fuse_client']) {
                // The client used our payment gateway. Create the charge object.
                $charge = $order->createCharge();
            } else {
                // Create a charge object only if the payment method is COD.
                if ($order->payment_method == 'cod') {
                    $charge = $order->createCharge();
                } else {
                    $charge = null;
                }
            }

            // Create the route plan if both pickup and delivery addresses are available.
            $segments = [];
            if ($pickup_address && $delivery_address) {
                $routes = Courier::ship($order->getAttributes(), $pickup_address->getAttributes(), $delivery_address->getAttributes());

                // We were able to create a route plan. Create the order segments.
                // TODO: Move this to a microservice.
                if ($routes) {
                    // Create the order segments.
                    foreach ($routes as $k => $route) {
                        $segments[$k] = $order->addSegment($route['courier_party_id'], $route['type'], $route['shipping_type'], $route['reference_id'], $route['barcode_format'], $route['pickup_address_id'], $route['delivery_address_id'], $route['start_date'], $route['end_date'], $route['currency_id'], $route['amount']);
                    }

                    // Set the active segment to be the first route.
                    $order->setActiveSegment($segments[0]->id);

                    // The order is ready for pick up.
                    if ($status == 'pending') {
                        $order->forPickup();
                    }
                } else {
                    // We were unable to determine a route plan.
                    // Accept the order but flag it.
                    $order->flag();
                }
            }

            // Add the relations.
            $order->items = $order_items;
            $order->charge = $charge;
            $order->segments = $segments;

            // Commit and return the order.
            DB::commit();
            return $order;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Updates a shipment order.
     */
    public function updateOrder($currency, $pickup_address, $delivery_address, $payment_method, $payment_provider, $status, $buyer_name, $email, $contact_number, $metadata, $preferred_pickup_time, $preferred_delivery_time, $match_status, $remarks)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Look for the currency ID.
            $currency_id = DB::table('core.currencies')->where('code', $currency)->value('id');

            if (!$currency_id) {
                throw new \Exception('The provided currency code is not valid.', 422);
            }

            // Set the currency.
            $this->currency_id = $currency_id;

            if ($this->pickup_address_id) {
                // Update the pickup address.
                $pickup_address = $this->pickupAddress()->first()->updateAddress('pickup', array_get($pickup_address, 'name'), array_get($pickup_address, 'line_1'), array_get($pickup_address, 'line_2'), array_get($pickup_address, 'city'), array_get($pickup_address, 'state'), array_get($pickup_address, 'postal_code'), array_get($pickup_address, 'country'), array_get($pickup_address, 'remarks'), array_get($pickup_address, 'title'), array_get($pickup_address, 'email'), array_get($pickup_address, 'phone_number'), array_get($pickup_address, 'mobile_number'), array_get($pickup_address, 'fax_number'), array_get($pickup_address, 'company'));
            } else {
                // Create the pickup address.
                $pickup_address = Address::store($this->party_id, 'pickup', array_get($pickup_address, 'name'), array_get($pickup_address, 'line_1'), array_get($pickup_address, 'line_2'), array_get($pickup_address, 'city'), array_get($pickup_address, 'state'), array_get($pickup_address, 'postal_code'), array_get($pickup_address, 'country'), array_get($pickup_address, 'remarks'), array_get($pickup_address, 'created_by'), array_get($pickup_address, 'title'), array_get($pickup_address, 'email'), array_get($pickup_address, 'phone_number'), array_get($pickup_address, 'mobile_number'), array_get($pickup_address, 'fax_number'), array_get($pickup_address, 'company'));

                // Set the pickup address.
                $this->pickup_address_id = $pickup_address->id;
            }

            if ($this->delivery_address_id) {
                // Update the delivery address.
                $delivery_address = $this->deliveryAddress()->first()->updateAddress('delivery', array_get($delivery_address, 'name'), array_get($delivery_address, 'line_1'), array_get($delivery_address, 'line_2'), array_get($delivery_address, 'city'), array_get($delivery_address, 'state'), array_get($delivery_address, 'postal_code'), array_get($delivery_address, 'country'), array_get($delivery_address, 'remarks'), array_get($delivery_address, 'title'), array_get($delivery_address, 'email'), array_get($delivery_address, 'phone_number'), array_get($delivery_address, 'mobile_number'), array_get($delivery_address, 'fax_number'), array_get($delivery_address, 'company'));
            } else {
                // Create the destination address.
                $delivery_address = Address::store($party_id, 'delivery', array_get($delivery_address, 'name'), array_get($delivery_address, 'line_1'), array_get($delivery_address, 'line_2'), array_get($delivery_address, 'city'), array_get($delivery_address, 'state'), array_get($delivery_address, 'postal_code'), array_get($delivery_address, 'country'), array_get($delivery_address, 'remarks'), array_get($delivery_address, 'created_by'), array_get($delivery_address, 'title'), array_get($delivery_address, 'email'), array_get($delivery_address, 'phone_number'), array_get($delivery_address, 'mobile_number'), array_get($delivery_address, 'fax_number'), array_get($delivery_address, 'company'));

                // Set the delivery address.
                $this->delivery_address_id = $delivery_address->id;
            }

            // Encode the metadata.
            $metadata = ($metadata) ? json_encode($metadata) : null;

            // Set the rest attributes.
            $this->payment_method = $payment_method;
            $this->payment_provider = $payment_provider;
            $this->status = $status;
            $this->buyer_name = $buyer_name;
            $this->email = $email;
            $this->contact_number = $contact_number;
            $this->metadata = $metadata;
            $this->preferred_pickup_time = $preferred_pickup_time;
            $this->preferred_delivery_time = $preferred_delivery_time;
            $this->match_status = $match_status;
            $this->remarks = $remarks;

            // Update the record.
            $this->save();

            // Update the route plan if both pickup and delivery addresses are available.
            // TODO: Move this to a microservice.
            $segments = [];
            if ($pickup_address && $delivery_address) {
                // Remove the active segment.
                $this->setActiveSegment(null);

                // Delete the old routes.
                $this->deleteSegments();

                // Generate the new route plan.
                $routes = Courier::ship($this->getAttributes(), $pickup_address->getAttributes(), $delivery_address->getAttributes());

                // We were able to create a route plan.
                if ($routes) {
                    // Create the order segments.
                    foreach ($routes as $k => $route) {
                        $segments[$k] = $this->addSegment($route['courier_party_id'], $route['type'], $route['shipping_type'], $route['reference_id'], $route['barcode_format'], $route['pickup_address_id'], $route['delivery_address_id'], $route['start_date'], $route['end_date'], $route['currency_id'], $route['amount']);
                    }

                    // Set the active segment to be the first route.
                    $this->setActiveSegment($segments[0]->id);
                } else {
                    // We were unable to determine a route plan. Flag the order.
                    $this->flag();
                }
            }

            // Add the relations.
            $this->segments = $segments;

            // Commit and return the updated order.
            DB::commit();
            return $this;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }
    
    /**
     * Creates a new charge.
     */
    public function createCharge($status = 'pending')
    {
        return Charge::store($this->id, $this->grand_total, $this->payment_method, $status);
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
     * Deletes the order segemnts.
     */
    public function deleteSegments()
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Fetch the segment IDs.
            $ids = DB::table('consumer.order_segments')->where('order_id', $this->id)->pluck('id');

            if ($ids) {
                // Delete the order events.
                DB::table('consumer.order_events')->whereIn('order_segment_id', $ids)->delete();

                // Delete the segments.
                DB::table('consumer.order_segments')->whereIn('id', $ids)->delete();
            }

            // Commit.
            DB::commit();
            return true;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
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
    public function setStatus($status, $remarks = null, $update_tat = true)
    {
        try {
            // Check if the current status is the same as the new one.
            if ($this->status == $status) {
                return $this;
            }

            // Start the transaction.
            DB::beginTransaction();

            // Update the order status.
            $this->status = $status;
            $this->status_updated_at = DB::raw('now()');
            $this->save();

            if ($update_tat) {
                $this->updateTat($status);
            }

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
     * Sets the the order match status.
     */
    public function setMatchStatus($status, $remarks = null)
    {
        // Check if the current status is the same as the new one.
        if ($this->match_status == $status) {
            return $this;
        }

        // Update the match status.
        $this->match_status = $status;
        $this->remarks = $remarks;
        $this->save();

        return $this;
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
     * Updates the order status to "picked_up" and sets the pickup date.
     */
    public function pickedUp($pickup_date, $remarks = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Set the status.
            $this->setStatus('picked_up', $remarks, false);

            // Set the TAT.
            $this->updateTat('picked_up', $pickup_date);

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
     * Sets the order status to "failed_delivery".
     */
    public function failedDelivery($remarks = null)
    {
        return $this->setStatus('failed_delivery', $remarks);
    }

    /**
     * Sets the order status to "claimed".
     */
    public function claimed($remarks = null)
    {
        return $this->setStatus('claimed', $remarks);
    }

    /**
     * Sets the order status to "out_for_delivery".
     */
    public function outForDelivery($remarks = null)
    {
        return $this->setStatus('out_for_delivery', $remarks);
    }

    /**
     * Sets the order status to "delivered".
     */
    public function delivered($remarks = null, $ip_address = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Set the status.
            $this->setStatus('delivered', $remarks);

            // Get the charge object.
            $charge = $this->charge()->first();

            // Transfer the fees from the client's fund wallet to the system's sales wallet.
            $this->transferSale($ip_address);

            // Set the charge status to "paid" if it's a COD order.
            if ($this->payment_method == 'cod') {
                if (!$charge) {
                    throw new \Exception('COD order has no charge.');
                }

                // Update the charge status.
                // TODO:
                // Determine where to get $tendered_amount, $change_amount, and $remarks.
                // Pass the order total for now.
                // $charge->paid($tendered_amount, $change_amount = 0, $remarks = null);
                $charge->paid($this->grand_total, 0, null);
            }

            // Transfers the total from the system's collection wallet to the client's fund wallet.
            if ($charge && $charge->status == 'paid') {
                $this->transferFunds($ip_address);
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
     * Sets the order status to "confirmed".
     */
    public function confirmed($remarks = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Set the status.
            $this->setStatus('confirmed', $remarks);

            // Get the charge object.
            $charge = $this->charge()->first();

            if ($charge) {
                // Set the charge object to paid.
                $charge->paid($this->grand_total);
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
     * Sets the order status to "return_in_transit".
     */
    public function returnInTransit($remarks = null)
    {
        return $this->setStatus('return_in_transit', $remarks);
    }

    /**
     * Sets the order status to "canceled".
     */
    public function canceled($remarks = null)
    {
        return $this->setStatus('canceled', $remarks);
    }

    /**
     * Sets the order status to "returned".
     */
    public function returned($remarks = null, $ip_address = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Update the order status.
            $this->setStatus('returned', $remarks);

            // Return the fees to the system wallet.
            $this->transferReturn($ip_address); 

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
     * Sets the order status to "failed_return".
     */
    public function failedReturn($remarks = null, $ip_address = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Update the order status.
            $this->setStatus('failed_return', $remarks);

            // Return the fees to the system wallet.
            $this->transferReturn($ip_address); 
            
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
     * Sets the match status to "matched".
     */
    public function txnMatch($remarks = null)
    {
        return $this->setMatchStatus('match', $remarks);
    }

    /**
     * Sets the match status to "over_remit".
     */
    public function txnOverRemit($remarks = null)
    {
        return $this->setMatchStatus('over_remit', $remarks);
    }

    /**
     * Sets the match status to "under_remit".
     */
    public function txnUnderRemit($remarks = null)
    {
        return $this->setMatchStatus('under_remit', $remarks);
    }

    /**
     * Claims an order.
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
    public function claimOrder($amount, $reason, $assets = null, $shipping_fee_flag = 0, $insurance_fee_flag = 0, $transaction_fee_flag = 0, $remarks = null, $status = 'pending', $reference_id = null, array $created_by = [])
    {
        return Claim::store($this->id, $amount, $reason, $assets, $shipping_fee_flag, $insurance_fee_flag, $transaction_fee_flag, $remarks, $status, $reference_id, $created_by);
    }

    /**
     * Checks if a pickup can be retried based on the client's contract.
     * The order status is set to "failed_pickup" if the retry is not allowed.
     */
    public function retryPickup($remarks = null)
    {
        // Get the client contract.
        $contract = Party::getContract($this->party_id);

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
        // Fetch the client contract.
        $contract = Party::getContract($party_id);

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

    /**
     * Transfer the shipping fee, insurance fee, and transaction fee from the client's fund wallet to the system's sales wallet.
     */
    private function transferSale($ip_address)
    {
        // Compute for the transfer amount.
        $amount = $this->shipping_fee + $this->insurance_fee + $this->transaction_fee;

        // Add the tracking number to the description.
        $details = 'Sales for order #' . $this->tracking_number;

        // Make the transfer from the client to the system wallet.
        return Wallet::transfer($this->party_id, config('settings.system_party_id'), 'fund', 'sales', $this->currency->code, $amount, 'sale', $details, $this->id, $ip_address);
    }

    /**
     * Transfers the total order amount from the system's collection wallet to the client's fund wallet.
     */
    private function transferFunds($ip_address)
    {
        // Add the tracking number to the description.
        $details = 'Funds for COD order #' . $this->tracking_number;

        // Transfer the total from the system's collection wallet to the client's fund wallet.
        return Wallet::transfer(config('settings.system_party_id'), $this->party_id, 'collections', 'fund', $this->currency->code, $this->grand_total, 'fund', $details, $this->id, $ip_address);
    }

    /**
     * Transfera the funds from the client's fund wallet to the system's sales wallet.
     */
    private function transferReturn($ip_address)
    {
        // We transfer twice the shipping fee plus insurance fee.
        $amount = ($this->shipping_fee * 2) + $this->insurance_fee;

        // Add the tracking number to the description.
        $details = 'Return for order #' . $this->tracking_number;
        
        // Transfer the fees back to the system wallet.
        return Wallet::transfer($this->party_id, config('settings.system_party_id'), 'fund', 'sales', $this->currency->code, $amount, 'return', $details, $this->id, $ip_address);
    }
}
