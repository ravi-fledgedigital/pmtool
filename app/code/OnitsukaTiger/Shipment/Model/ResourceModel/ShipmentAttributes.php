<?php
namespace OnitsukaTiger\Shipment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class NetsuiteFulfillment
 * @package OnitsukaTiger\Shipment\Model\ResourceModel
 */
class ShipmentAttributes extends AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('shipment_extension_attributes', 'id');
    }
}
