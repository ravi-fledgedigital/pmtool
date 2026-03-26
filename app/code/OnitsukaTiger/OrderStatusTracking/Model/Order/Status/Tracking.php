<?php
namespace OnitsukaTiger\OrderStatusTracking\Model\Order\Status;

use Magento\Framework\Model\AbstractModel;

class Tracking extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\OnitsukaTiger\OrderStatusTracking\Model\ResourceModel\Order\Status\Tracking::class);
    }
}
