<?php
declare(strict_types=1);

namespace OnitsukaTiger\NetSuite\Model;

use Magento\Framework\Model\AbstractModel;
use OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes as ResourceModel;

/**
 * Class ShipmentAttributes
 * @package OnitsukaTigerKorea\Rma\Model
 */
class ShipmentAttributes extends \OnitsukaTiger\Shipment\Model\ShipmentAttributes
{
    /**
     * @return bool|int
     */
    public function getShipmentStoreSynced()
    {
        return $this->getData('shipment_store_synced');
    }

    /**
     * @param $flag
     * @return ShipmentAttributes
     */
    public function setShipmentStoreSynced($flag)
    {
        return $this->setData('shipment_store_synced', $flag);
    }
}
