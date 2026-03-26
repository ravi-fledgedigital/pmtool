<?php
namespace OnitsukaTiger\KerryConNo\Model;

use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as TrackCollectionFactory;

/**
 * Class TrackingNumber
 * @package OnitsukaTiger\KerryConNo\Model
 */
class TrackingNumber
{
    /** @var  \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection */
    protected $trackingCollection;

    /**
     * @var \OnitsukaTiger\Logger\Logger
     */
    protected $logger;

    /**
     * TrackingNumber constructor.
     * @param TrackCollectionFactory $collectionFactory
     */
    public function __construct(
        TrackCollectionFactory $collectionFactory,
        \OnitsukaTiger\Logger\Api\Logger $logger
    )
    {
        $this->logger = $logger;
        $this->trackingCollection = $collectionFactory->create();
    }

    /**
     * @param $trackNo
     * @return bool|Shipment
     */
    public function getShipmentFromTrackingNumber($trackNo)
    {
        try {
            // clear collection
            $this->trackingCollection->clear();
            // clear where
            $this->trackingCollection->getSelect()->reset(\Zend_Db_Select::WHERE);

            $this->trackingCollection
                ->addFieldToFilter(ShipmentTrackInterface::TRACK_NUMBER, $trackNo);
            /** @var Shipment\Track $tracking */
            $tracking = $this->trackingCollection->getFirstItem();
            /** @var Shipment $shipment */
            $shipment = $tracking->getShipment();

            return $shipment;
        }
        catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }


    /**
     * @param $trackNo
     * @return bool|float|string|null
     */
    public function getOrderFromTrackingNumber($trackNo)
    {
        $shipment = $this->getShipmentFromTrackingNumber($trackNo);
        if($shipment){
            return $shipment->getOrder();
        }
        return false;
    }
}
