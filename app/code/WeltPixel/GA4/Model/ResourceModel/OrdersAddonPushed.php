<?php
namespace WeltPixel\GA4\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OrdersAddonPushed extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('weltpixel_ga4_orders_addon_pushed', 'id');
    }

    /**
     * Find pushed_id by order_id using direct query
     *
     * @param int $orderId
     * @return int|null
     */
    public function getPushedIdByOrderId($orderId)
    {
        $connection = $this->getConnection();
        
        $select = $connection->select()
            ->from($this->getMainTable(), ['id'])
            ->where('order_id = ?', $orderId)
            ->limit(1);

        return $connection->fetchOne($select);
    }
} 