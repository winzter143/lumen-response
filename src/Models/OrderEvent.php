<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class OrderEvent extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'consumer.order_events';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['id', 'order_segment_id', 'status', 'remarks', 'created_by', 'created_at'];

    /**
     * Turn off timestamps. This will prevent Laravel from updating created_at and updated_at.
     */
    public $timestamps = false;

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        // Set the validation rules.
        $rules = [
            'order_segment_id' => 'integer|nullable|exists:pgsql.consumer.order_segments,id',
            'status' => 'string|in:' . implode(',', array_keys(config('settings.order_statuses')))
        ];

        return $rules;
    }

    /**
     * Creates a new order event.
     */
    public static function store($order_segment_id, $status, $remarks = null)
    {
        try {
            // Build the list of attributes to be saved.
            $attributes = [
                'order_segment_id' => $order_segment_id,
                'status' => $status,
                'remarks' => $remarks
            ];

            // Create the order event.
            $order = self::create($attributes);
            return $order;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
