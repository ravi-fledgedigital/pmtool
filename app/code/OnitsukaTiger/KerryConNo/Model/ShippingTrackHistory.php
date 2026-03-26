<?php
namespace OnitsukaTiger\KerryConNo\Model;

use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as TrackCollectionFactory;

/**
 * Class TrackingNumber
 * @package OnitsukaTiger\KerryConNo\Model
 */
class ShippingTrackHistory extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'onitsukatiger_kery_shippingtrackHistory';

    protected $_cacheTag = 'onitsukatiger_kery_shippingtrackHistory';

    protected $_eventPrefix = 'onitsukatiger_kery_shippingtrackHistory';

    /**
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('OnitsukaTiger\KerryConNo\Model\ResourceModel\ShippingTrackHistory');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
