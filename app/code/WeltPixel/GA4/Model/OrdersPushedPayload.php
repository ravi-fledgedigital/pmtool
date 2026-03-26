<?php
namespace WeltPixel\GA4\Model;

use Magento\Framework\Model\AbstractModel;

class OrdersPushedPayload extends AbstractModel
{
    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init(\WeltPixel\GA4\Model\ResourceModel\OrdersPushedPayload::class);
    }

    /**
     * @param int $orderId
     * @return string|null
     */
    public function getPayloadByOrderId($orderId)
    {
        /** @var \WeltPixel\GA4\Model\ResourceModel\OrdersPushedPayload $resource */
        $resource = $this->getResource();
        return $resource->getPayloadByOrderId($orderId);
    }
}
