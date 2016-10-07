<?php
namespace F3\Components;

use DB;
use Cache;
use F3\Models\Address;

/**
 * Abstract class for partner couriers.
 */
abstract class Courier
{
    /**
     * List of couriers.
     * Note: The ordering of the list affects the behavior of the pickup and delivery methods.
     * @var array $couriers
     */
    private static $couriers = [
        'lbcx_yakal' => 'LBCXYakal',
        'lbc' => 'LBC',
        'lbcx_park_square' => 'LBCXParkSquare',
        'lbcx_greenhills' => 'LBCXGreenhills',
        'lbcx_qc' => 'LBCXQC',
        'lbcx_cebu' => 'LBCXCebu',
    ];

    /**
     * Constructor.
     */
    abstract public function __construct($party_id, $name, array $warehouse, array $metadata = []);

    /**
     * Returns the courier ID.
     */
    abstract public function getId();

    /**
     * Returns the courier name.
     */
    abstract public function getName();

    /**
     * Returns the courier warehouse address.
     */
    abstract public function getWarehouse();

    /**
     * Returns the courier metadata.
     */
    abstract public function getMetadata();

    /**
     * Returns an array of pick up areas.
     */
    abstract public function getPickupAreas();

    /**
     * Returns an array of delivery areas.
     */
    abstract public function getDeliveryAreas();

    /**
     * Returns a reference ID / tracking number.
     */
    abstract public function getReferenceId($default = null);

    /**
     * Converts the class name to key.
     * @param string $name
     */
    protected static function classKey($name)
    {
        return strtolower(str_replace(' ', '_', $name));
    }

    /**
     * Returns all the courier classes.
     */
    public static function getCouriers()
    {
        // Fetch the couriers from the DB.
        $couriers = DB::table('core.organizations as o')
            ->select(['o.party_id', 'o.name', 'p.metadata', DB::raw('to_json(a.*) as warehouse')])
            ->join('core.parties as p', 'p.id', '=', 'o.party_id')
            ->join('core.addresses as a', 'a.party_id', '=', 'o.party_id')
            ->where([['o.type', 'courier'], ['p.status', '1'], ['a.type', 'warehouse']])
            ->get();

        // Sort the couriers.
        $sort = [];
        foreach (self::$couriers as $k => $v) {
            foreach ($couriers as $courier) {
                if ($k == self::classKey($courier['name'])) {
                    $sort[] = $courier;
                }
            }
        }

        // Update $couriers with the sorted list.
        $couriers = $sort;

        // Instantiate the classes.
        return array_map(function($courier) {
            // Decode the data.
            $courier['warehouse'] = json_decode($courier['warehouse'], true);
            $courier['warehouse'] = ($courier['warehouse']) ? $courier['warehouse'] : [];
            $courier['metadata'] = json_decode($courier['metadata'], true);
            $courier['metadata'] = ($courier['metadata']) ? $courier['metadata'] : [];

            // Instantiate the class.
            $class = 'F3\Components\Couriers\\' . self::$couriers[self::classKey($courier['name'])];
            return new $class($courier['party_id'], $courier['name'], $courier['warehouse'], $courier['metadata']);
        }, $couriers);
    }

    /**
     * Returns the requested courier class.
     * @param string $name Courier name
     */
    public static function getCourier($name)
    {
        // Get the couriers.
        $couriers = self::getCouriers();

        foreach ($couriers as $courier) {
            if (strtolower($courier->getName()) == strtolower($name)) {
                return $courier;
            }
        }

        return false;
    }

    /**
     * Determines the pickup and delivery couriers.
     * @param array $order
     * @param array $pickup_address
     * @param array $delivery_address
     */
    public static function ship(array $order, array $pickup_address, array $delivery_address)
    {
        // Determine the pickup courier.
        $pickup = self::pickup($order, $pickup_address);

        if (!$pickup) {
            throw new \Exception('No pickup courier available.');
        }

        // Determine the delivery courier.
        $delivery = self::deliver($order, $delivery_address);

        if (!$delivery) {
            throw new \Exception('No delivery courier available.');
        }

        // Get the warehouse address of the pickup courier.
        $warehouse = $pickup->getWarehouse();

        // Create the pickup route.
        $routes[] = [
            'order_id' => $order['id'],
            'courier' => $pickup->getName(),
            'courier_party_id' => $pickup->getId(),
            'type' => 'pick_up',
            'pickup_address' => $pickup_address,
            'pickup_address_id' => $pickup_address['id'],
            'delivery_address' => $warehouse,
            'delivery_address_id' => $warehouse['id'],
            'start_date' => $order['created_at'],
            'end_date' => null,
            'reference_id' => $pickup->getReferenceId($order['tracking_number']),
            'barcode_format' => array_get($pickup->metadata, 'barcode_format'),

            // TODO: patch these later when we integrate with 3rd party couriers.
            'shipping_type' => config('settings.defaults.shipping_type'),
            'currency' => null,
            'currency_id' => null,
            'amount' => null,
        ];

        // Create the delivery route.
        $routes[] = [
            'order_id' => $order['id'],
            'courier' => $delivery->getName(),
            'courier_party_id' => $delivery->getId(),
            'type' => 'delivery',
            'pickup_address' => $warehouse,
            'pickup_address_id' => $warehouse['id'],
            'delivery_address' => $delivery_address,
            'delivery_address_id' => $delivery_address['id'],
            'start_date' => null,
            'end_date' => null,
            'reference_id' => $delivery->getReferenceId($order['tracking_number']),
            'barcode_format' => array_get($delivery->metadata, 'barcode_format'),

            // TODO: patch these later when we integrate with 3rd party couriers.
            'shipping_type' => config('settings.defaults.shipping_type'),
            'currency' => null,
            'currency_id' => null,
            'amount' => null,
        ];

        // Return the routes and let the caller create the segments.
        return $routes;
    }

    /**
     * Determines which courier should pick up the order.
     * @param array $order
     * @param array $pickup_address
     */
    public static function pickup(array $order, array $pickup_address)
    {
        // Fetch the couriers.
        $couriers = self::getCouriers();

        // Note: For now, look for the first courier that can pick up the order.
        // We can improve on this later.
        foreach ($couriers as $courier) {
            if ($courier->picksUpFrom($pickup_address)) {
                return $courier;
            }
        }

        return false;
    }

    /**
     * Determines which courier should deliver the order.
     * @param array $order
     * @param array $pickup_address
     */
    public static function deliver(array $order, array $delivery_address)
    {
        // Fetch the couriers.
        $couriers = self::getCouriers();

        // Note: For now, look for the first courier that can deliver the order.
        // We can improve on this later.
        foreach ($couriers as $courier) {
            if ($courier->deliversTo($delivery_address)) {
                return $courier;
            }
        }

        return false;
    }

    /**
     * Checks if the pickup address is servicable by the courier.
     * @param array $address
     */
    public function picksUpFrom($address)
    {
        // Format the pickup address.
        $address = Address::format($address, ', ');

        // Get the pickup areas.
        $areas = $this->getPickupAreas();

        // The courier picks up from anywhere.
        if ($areas == '*') {
            return true;
        }

        // The address is not serviceable by the courier.
        if (!$areas) {
            return false;
        }

        // Check if the pickup address is serviceable by the courier.
        foreach ($areas as $area) {
            if (strpos(strtolower($address), strtolower($area)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the delivery address is servicable by the courier.
     * @param array $address
     */
    public function deliversTo($address)
    {
        // Format the delivery address.
        $address = Address::format($address, ', ');

        // Get the delviery areas.
        $areas = $this->getDeliveryAreas();

        // The courier delivers to anywhere.
        if ($areas == '*') {
            return true;
        }

        // The address is not serviceable by the courier.
        if (!$areas) {
            return false;
        }

        // Check if the delivery address is serviceable by the courier.
        foreach ($areas as $area) {
            if (strpos(strtolower($address), strtolower($area)) !== false) {
                return true;
            }
        }

        return false;
    }

}
