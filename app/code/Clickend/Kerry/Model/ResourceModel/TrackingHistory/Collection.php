<?php
/**
 * Clickend Kerry module
 * @package Clickend\Kerry
 */
declare(strict_types=1);

namespace Clickend\Kerry\Model\ResourceModel\TrackingHistory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Clickend\Kerry\Model\TrackingHistory::class,
            \Clickend\Kerry\Model\ResourceModel\TrackingHistory::class
        );
    }
    /**
     * @param string $orderId
     * @return Collection
     */
    public function getByOrderId(string $orderId)
    {
        return $this->_reset()
            ->addFieldToFilter('order_id', $orderId)
            ->load();
    }
}
