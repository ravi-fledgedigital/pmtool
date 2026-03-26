<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\Sales\Model\Shipment;

use Magento\Framework\Model\AbstractModel;
use OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes as ResourceModel;

/**
 * Class ShipmentAttributes
 * @package OnitsukaTigerKorea\Sales\Model
 */
class ShipmentAttributes extends \OnitsukaTiger\Shipment\Model\ShipmentAttributes
{
    /**
     * @return bool|int
     */
    public function getExportSaleDataFlag()
    {
        return $this->getData('export_sale_data_flag');
    }

    /**
     * @param bool|int $flag
     * @return $this
     */
    public function setExportSaleDataFlag($flag)
    {
        return $this->setData('export_sale_data_flag', $flag);
    }
}

