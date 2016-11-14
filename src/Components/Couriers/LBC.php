<?php
namespace F3\Components\Couriers;

use DB;
use F3\Components\Courier;

/**
 * LBC Express.
 */
class LBC extends Courier
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
     * Hubs
     * @param array $hubs
     */
    protected $hubs;

    /**
     * Selected hub
     * @param array $hub
     */
    protected $hub;

    /**
     * Metadata
     * @param array $metadata
     */
    protected $metadata;
    
    /**
     * Constructor.
     */
    public function __construct($party_id, $name, array $hubs, $hub = null, array $metadata = [])
    {
        $this->party_id = $party_id;
        $this->name = $name;
        $this->hubs = $hubs;
        $this->hub = $hub;
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
     * Returns the courier hubs.
     */
    public function getHubs()
    {
        return $this->hubs;
    }

    /**
     * Returns the selected hub.
     */
    public function getHub()
    {
        return $this->hub;
    }

    /**
     * Returns the courier metadata.
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Returns a reference ID / tracking number.
     */
    public function getReferenceId($default = null)
    {
        // Generate a tracking number.
        return (string)DB::select('select nextval(:sequence)', ['sequence' => 'consumer.lbc_tracking_number_seq'])[0]['nextval'];
    }

    /**
     * Returns true if the courier is a third party courier.
     */
    public function isThirdParty()
    {
        return true;
    }
}
