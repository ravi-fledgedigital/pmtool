<?php
namespace OnitsukaTiger\OrderStatusTracking\Model\ResourceModel\Order\Status\Tracking;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Data\Collection as DataCollection;

class Collection extends AbstractCollection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\OrderStatusTracking\Model\Order\Status\Tracking::class,
            \OnitsukaTiger\OrderStatusTracking\Model\ResourceModel\Order\Status\Tracking::class
        );
    }

    /**
     * @param int $orderId
     * @return Collection
     */
    public function getByOrderId(int $orderId)
    {
        return $this->_reset()
            ->addFieldToFilter('parent_id', $orderId)
            ->setOrder('created_at', DataCollection::SORT_ORDER_ASC)
            ->load();
    }

    /**
     * @param int $orderId
     * @param string $status
     * @return Collection
     */
    public function getByOrderIdStatus(int $orderId, string $status)
    {
        return $this->_reset()
            ->addFieldToFilter('parent_id', $orderId)
            ->addFieldToFilter('status', $status)
            ->load();
    }
}
