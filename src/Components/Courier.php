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
     * Constructor.
     * @param int $party_id Organization party ID
     * @param string $name Organization name
     * @param array $hubs Hubs
     * @param array $hub Selected hub
     * @param array $metadata Metadata
     */
    abstract public function __construct($party_id, $name, array $hubs, $hub = null, array $metadata = []);

    /**
     * Returns the courier ID.
     */
    abstract public function getId();

    /**
     * Returns the courier name.
     */
    abstract public function getName();

    /**
     * Returns the courier hubs.
     */
    abstract public function getHubs();

    /**
     * Returns the selected hub.
     */
    abstract public function getHub();

    /**
     * Returns the courier metadata.
     */
    abstract public function getMetadata();

    /**
     * Returns a reference ID / tracking number.
     * @param string $default Reference ID
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
            ->select(['o.party_id', 'o.name', 'p.metadata'])
            ->join('core.parties as p', 'p.id', '=', 'o.party_id')
            ->join('core.party_roles as pr', 'pr.party_id', '=', 'o.party_id')
            ->join('core.roles as r', 'r.id', '=', 'pr.role_id')
            ->where([['r.name', 'courier'], ['p.status', '1'],])
            ->get();

        if (!$couriers) {
            throw new \Exception('There are no available couriers.');
        }

        // Convert it to array.
        $couriers = $couriers->toArray();

        // Sort the couriers by priority.
        $sort = [];
        foreach ($couriers as $k => $courier) {
            // Decode the metadata.
            $courier['metadata'] = json_decode($courier['metadata'], true);
            $sort[$courier['metadata']['priority']] = $courier;
        }

        // Sort the array.
        ksort($sort);
        $couriers = $sort;

        // Get the courier hubs.
        $hubs = self::getCourierHubs(array_column($couriers, 'party_id'));

        if (!$hubs) {
            throw new \Exception('There are no hubs for the given couriers.');
        }

        // Assign the hubs to the couriers.
        foreach ($couriers as $k => $courier) {
            $couriers[$k]['hubs'] = [];
            foreach ($hubs as $hub) {
                if ($hub['courier_party_id'] == $courier['party_id']) {
                    $couriers[$k]['hubs'][] = $hub;
                }
            }
        }

        // Instantiate the classes.
        return array_map(function($courier) {
            // Instantiate the class.
            return self::newCourier($courier);
        }, $couriers);
    }

    /**
     * Instantiates a courier object.
     * @param array $courier
     */
    public static function newCourier($courier)
    {
        if (is_array($courier)) {
            $class = 'F3\Components\Couriers\\' . self::classKey($courier['name']);
            return new $class($courier['party_id'], $courier['name'], $courier['hubs'], isset($courier['hub']) ? $courier['hub'] : null, $courier['metadata']);
        } else {
            $class = 'F3\Components\Couriers\\' . self::classKey($courier->name);
            return new $class($courier->party_id, $courier->name, $courier->hubs, $courier->hub, $courier->metadata);
        }
    }

    /**
     * Returns the the courier hubs.
     * @param array $couriers Array of courier party IDs
     */
    private static function getCourierHubs(array $couriers)
    {
        // Fetch the hubs from the DB.
        $hubs = DB::table('core.organizations as o')
            ->select(['o.party_id', 'o.name', 'p.metadata', DB::raw('to_json(a.*) as business'), 'rel.to_party_id as courier_party_id'])
            ->join('core.parties as p', 'p.id', '=', 'o.party_id')
            ->join('core.addresses as a', 'a.party_id', '=', 'o.party_id')
            ->join('core.party_roles as pr', 'pr.party_id', '=', 'o.party_id')
            ->join('core.roles as role', 'role.id', '=', 'pr.role_id')
            ->join('core.relationships as rel', 'rel.from_party_id', '=', 'o.party_id')
            ->where([['role.name', 'hub'], ['p.status', '1'], ['a.type', 'business'], ['rel.type', 'department_of']])
            ->whereIn('rel.to_party_id', $couriers)
            ->get();

        if ($hubs) {
            $hubs = $hubs->toArray();
            foreach ($hubs as $k => $hub) {
                // Decode the metadata and business address.
                $hubs[$k]['metadata'] = json_decode($hub['metadata'], true);
                $hubs[$k]['business'] = json_decode($hub['business'], true);
            }
        }

        return $hubs;
    }

    /**
     * Determines the pickup and delivery couriers.
     * @param array $order
     * @param array $pickup_address
     * @param array $delivery_address
     */
    public static function ship(array $order, array $pickup_address, array $delivery_address)
    {
        // Fetch the couriers.
        $couriers = self::getCouriers();

        // Determine the pickup courier.
        $pickup = self::pickup($couriers, $order, $pickup_address);

        if (!$pickup) {
            throw new \Exception('No pickup courier available.');
        }

        // Determine the delivery courier.
        $delivery = self::deliver($couriers, $order, $delivery_address);

        if (!$delivery) {
            throw new \Exception('No delivery courier available.');
        }

        // Determine if the shipment needs to be transferred to other hubs before the delivery segment.
        $transfer = self::transfer($couriers, $pickup, $delivery);

        // Create the pickup route.
        $routes[] = self::createRoute('pick_up', $order, $pickup, $pickup_address, $pickup->hub['business']);

        // Create the transfer route (if needed).
        if ($transfer) {
            $routes[] = self::createRoute('transfer', $order, $transfer, $pickup->hub['business'], $delivery->hub['business']);
        }

        // Create the delivery route.
        $routes[] = self::createRoute('delivery', $order, $delivery, $delivery->hub['business'], $delivery_address);

        // Return the routes and let the caller create the segments.
        return $routes;
    }

    /**
     * Creates a route segment.
     * @param string $type Route type (pick_up | delivery | transfer)
     * @param array $order Order details
     * @param F3\Components\Courier $courier Courier object that will handle the shipment
     * @param array $from Pickup address
     * @param array $to Delivery address
     */
    private static function createRoute($type, array $order, Courier $courier, array $from, array $to)
    {
        return [
            'order_id' => $order['id'],
            'type' => $type,
            'courier' => $courier->getName(),
            'courier_party_id' => $courier->getId(),
            'pickup_address' => $from,
            'pickup_address_id' => $from['id'],
            'delivery_address' => $to,
            'delivery_address_id' => $to['id'],
            'start_date' => null,
            'end_date' => null,
            'reference_id' => $courier->getReferenceId($order['tracking_number']),
            'barcode_format' => array_get($courier->metadata, 'barcode_format'),

            // TODO: patch these later when we integrate with 3rd party couriers.
            'shipping_type' => config('settings.defaults.shipping_type'),
            'currency' => null,
            'currency_id' => null,
            'amount' => null,
        ];
    }

    /**
     * Determines which courier should pick up the order.
     * @param array $couriers
     * @param array $order
     * @param array $pickup_address
     */
    public static function pickup(array $couriers, array $order, array $pickup_address)
    {
        // Note: For now, look for the first courier that can pick up the order.
        // We can improve on this later.
        foreach ($couriers as $courier) {
            // Determine the hub responsible for pickup.
            $courier->hub = $courier->getPickupHub($pickup_address);

            if ($courier->hub) {
                return self::newCourier($courier);
            }
        }

        return false;
    }

    /**
     * Determines which courier should deliver the order.
     * @param array $couriers
     * @param array $order
     * @param array $pickup_address
     */
    public static function deliver(array $couriers, array $order, array $delivery_address)
    {
        // Note: For now, look for the first courier that can deliver the order.
        // We can improve on this later.
        foreach ($couriers as $courier) {
            // Determine the hub responsible for delivery.
            $courier->hub = $courier->getDeliveryHub($delivery_address);

            if ($courier->hub) {
                return self::newCourier($courier);
            }
        }

        return false;
    }

    /**
     * Determine if the shipment needs to be transferred to other hubs before the delivery segment.
     * @param array $couriers
     * @param F3\Components\Courier $pickup
     * @param F3\Components\Courier $delivery
     */
    public static function transfer(array $couriers, $pickup, Courier $delivery)
    {
        if ($pickup->hub['party_id'] == $delivery->hub['party_id']) {
            // The pickup hub is the same as the delivery hub. No need to transfer.
            return false;
        } else {
            // The pickup hub is different from the delivery hub. The pickup hub will process the transfer.
            return self::newCourier($pickup);
        }
    }

    /**
     * Determines the hub responsible for pickup.
     * @param array $address
     */
    public function getPickupHub($address)
    {
        // Format the pickup address.
        $address = Address::format($address, ', ');

        if (!$this->hubs) {
            return false;
        }

        // Loop through the hubs and determine which hub is responsible for pickup.
        foreach ($this->hubs as $hub) {
            // Get the pickup areas.
            $areas = array_get($hub, 'metadata.areas.pickup');

            if (!$areas) {
                return false;
            }

            // Check if the hub covers all areas.
            if ($areas == '*') {
                return $hub;
            }

            // Check if the pickup address is serviceable by the hub.
            foreach ($areas as $area) {
                if (strpos(strtolower($address), strtolower($area)) !== false) {
                    return $hub;
                }
            }
        }

        return false;
    }

    /**
     * Checks if the delivery address is servicable by the courier.
     * @param array $address
     */
    public function getDeliveryHub($address)
    {
        // Format the delivery address.
        $address = Address::format($address, ', ');

        if (!$this->hubs) {
            return false;
        }

        // Loop through the hubs and determine which hub is responsible for delivery.
        foreach ($this->hubs as $hub) {
            // Get the delivery areas.
            $areas = array_get($hub, 'metadata.areas.delivery');

            if (!$areas) {
                return false;
            }

            // Check if the hub covers all areas.
            if ($areas == '*') {
                return $hub;
            }

            // Check if the delivery address is serviceable by the hub.
            foreach ($areas as $area) {
                if (strpos(strtolower($address), strtolower($area)) !== false) {
                    return $hub;
                }
            }
        }

        return false;
    }

}
