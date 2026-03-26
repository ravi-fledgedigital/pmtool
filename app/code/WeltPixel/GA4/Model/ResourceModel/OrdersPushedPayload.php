<?php
namespace WeltPixel\GA4\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OrdersPushedPayload extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('weltpixel_ga4_orders_pushed_payload', 'id');
    }

    /**
     * Get stored payload by order id
     *
     * @param int $orderId
     * @return string|null
     */
    public function getPayloadByOrderId($orderId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['order_payload'])
            ->where('order_id = ?', $orderId)
            ->limit(1);

        $result = $connection->fetchOne($select);
        return $result !== false ? $result : null;
    }
}
