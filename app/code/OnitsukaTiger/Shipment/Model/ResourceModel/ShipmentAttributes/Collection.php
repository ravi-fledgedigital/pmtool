<?php
declare(strict_types=1);

namespace OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes as ResourceModel;
use OnitsukaTiger\Shipment\Model\ShipmentAttributes as Model;

/**
 * Class Collection
 * @package OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
