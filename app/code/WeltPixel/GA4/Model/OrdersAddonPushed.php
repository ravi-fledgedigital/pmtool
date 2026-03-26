<?php
namespace WeltPixel\GA4\Model;

use Magento\Framework\Model\AbstractModel;

class OrdersAddonPushed extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\WeltPixel\GA4\Model\ResourceModel\OrdersAddonPushed::class);
    }

    /**
     * Find pushed_id by order_id
     *
     * @param int $orderId
     * @return int|null
     */
    public function getPushedIdByOrderId($orderId)
    {
        return $this->getResource()->getPushedIdByOrderId($orderId);
    }
} 