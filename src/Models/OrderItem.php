<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class OrderItem extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'consumer.order_items';

    /**
     * Turn off timestamps. This will prevent Laravel from updating created_at and updated_at.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['order_id', 'type', 'description', 'amount', 'quantity', 'total', 'metadata'];
    
    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [
            'order_id' => 'integer|required|exists:pgsql.consumer.orders,id',
            'type' => 'string|in:product,shipping,tax,fee,insurance',
            'description' => 'string|required',
            'amount' => 'numeric|required|min:0|max:999999999999.99',
            'quantity' => 'integer|required|min:1',
            'total' => 'numeric|required|min:0|max:999999999999.99',
            'metadata' => 'json|nullable'
        ];
    }

    /**
     * Creates a new order item.
     */
    public static function store($order_id, $type, $description, $amount, $quantity = 1, $metadata = null)
    {
        // Compute for the total.
        $amount = round($amount, 2);
        $quantity = ($quantity && $type == 'product') ? $quantity : 1;
        $total = round($amount * $quantity, 2);

        // Encode the metadata.
        $metadata = ($metadata) ? json_encode($metadata) : null;

        // Build the list of attributes to be saved.
        $attributes = array_merge([
            'order_id' => $order_id,
            'type' => $type,
            'description' => $description,
            'amount' => $amount,
            'quantity' => $quantity,
            'total' => $total,
            'metadata' => $metadata
        ]);

        // Create the order item.
        return self::create($attributes);
    }
}
