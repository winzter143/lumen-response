<?php
namespace F3\Components\Couriers;

use F3\Components\Courier;

/**
 * LBCX Cebu.
 */
class LBCXCebu extends Courier
{
    /**
     * Party ID
     * @param int $party_id
     */
    protected $party_id;

    /**
     * Name
     * @param string $name
     */
    protected $name;

    /**
     * Warehouse address
     * @param array $warehouse
     */
    protected $warehouse;

    /**
     * Metadata
     * @param array $metadata
     */
    protected $metadata;
    
    /**
     * Constructor.
     */
    public function __construct($party_id, $name, array $warehouse, array $metadata = [])
    {
        $this->party_id = $party_id;
        $this->name = $name;
        $this->warehouse = $warehouse;
        $this->metadata = $metadata;
    }

    /**
     * Returns the courier ID.
     */
    public function getId()
    {
        return $this->party_id;
    }

    /**
     * Returns the courier name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the courier warehouse address.
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }
    
    /**
     * Returns the courier metadata.
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Returns an array of pick up areas.
     */
    public function getPickupAreas()
    {
        // Not implemented.
        return [];
    }

    /**
     * Returns an array of delivery areas.
     */
    public function getDeliveryAreas()
    {
        // Not implemented.
        return [];
    }

    /**
     * Returns a reference ID / tracking number.
     */
    public function getReferenceId($default = null)
    {
        return $default;
    }
}
