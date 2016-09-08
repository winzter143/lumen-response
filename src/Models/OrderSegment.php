<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class OrderSegment extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'consumer.order_segments';

    /**
     * Turn off timestamps. This will prevent Laravel from updating created_at and updated_at.
     */
    public $timestamps = false;
    
    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['order_id', 'courier_party_id', 'shipping_type', 'currency_id', 'amount', 'reference_id', 'pickup_address_id', 'delivery_address_id', 'start_date', 'end_date', 'flagged', 'created_at', 'status', 'barcode_format_1d', 'barcode_format_2d'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [
            'order_id' => 'integer|required|exists:pgsql.consumer.orders,id',
            'courier_party_id' => 'integer|required|exists:pgsql.core.organizations,party_id',
            'shipping_type' => 'string|required|in:land,sea,air',
            'currency_id' => 'integer|required|exists:pgsql.core.currencies,id',
            'amount' => 'numeric|required|min:0|max:999999999999.99',
            'reference_id' => 'string|required|max:100',
            'pickup_address_id' => 'integer|required|exists:pgsql.core.addresses,id',
            'delivery_address_id' => 'integer|required|exists:pgsql.core.addresses,id',
            'start_date' => 'string',
            'end_date' => 'string',
            'flagged' => 'integer|required|in:0,1'
        ];
    }

    /**
     * Creates a new segment.
     */
    public static function store($order_id, $courier_party_id, $shipping_type, $currency_id, $amount, $reference_id, $pickup_address_id, $delivery_address_id, $start_date, $end_date, $flagged, $status = 'pending', $barcode_format_1d = 'code_128', $barcode_format_2d = 'qr')
    {
        try {
            // Build the attribute list.
            $attributes = [
                'order_id' => $order_id,
                'courier_party_id' => $courier_party_id,
                'shipping_type' => $shipping_type,
                'currency_id' => $currency_id,
                'amount' => $amount,
                'reference_id' => $reference_id,
                'pickup_address_id' => $pickup_address_id,
                'delivery_address_id' => $delivery_address_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'flagged' => $flagged,
                'status' => $status,
                'barcode_format_1d' => $barcode_format_1d,
                'barcode_format_2d' => $barcode_format_2d
            ];

            // Create the charge. 
            return self::create($attributes);
        } catch (\Exception $e) {
            // Rollback and return the error.
            throw $e;
        }
    }
}
