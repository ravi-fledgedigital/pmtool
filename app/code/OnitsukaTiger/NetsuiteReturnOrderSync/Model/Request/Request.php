<?php
namespace OnitsukaTiger\NetsuiteReturnOrderSync\Model\Request;

use Amasty\Base\Model\Serializer;
use Amasty\Rma\Api\Data\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Request extends \Amasty\Rma\Model\Request\Request
{
    /**
     * Constants defined for keys of data array
     */
    const SHIPMENT_INCREMENT_ID = 'shipment_increment_id';

    /**
     * @param string $shipmentIncrementId
     *
     * @return RequestInterface
     */
    public function setShipmentIncrementId(string $shipmentIncrementId)
    {
        return $this->setData(self::SHIPMENT_INCREMENT_ID, $shipmentIncrementId);
    }

    /**
     * @return mixed|null
     */
    public function getShipmentIncrementId()
    {
        return $this->_getData(self::SHIPMENT_INCREMENT_ID);
    }
}
