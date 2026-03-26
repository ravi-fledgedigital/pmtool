<?php
namespace WeltPixel\GA4\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OrdersAddonTypes extends AbstractDb
{
    /**
     * Initialize resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('weltpixel_ga4_orders_addon_types', 'id');
    }

    /**
     * Update is_pushed status for a specific addon type using direct query
     *
     * @param int $pushedId
     * @param string $addonType
     * @return int Number of affected rows
     */
    public function updatePushStatus($pushedId, $addonType)
    {
        $connection = $this->getConnection();

        return $connection->update(
            $this->getMainTable(),
            ['is_pushed' => 1],
            [
                'pushed_id = ?' => $pushedId,
                'addon_type = ?' => $addonType,
                'is_pushed = ?' => 0
            ]
        );
    }

    /**
     * Check if addon type is already processed using direct query
     *
     * @param int $pushedId
     * @param string $addonType
     * @return bool
     */
    public function isAddonTypeProcessed($pushedId, $addonType)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('pushed_id = ?', $pushedId)
            ->where('addon_type = ?', $addonType)
            ->where('is_pushed = ?', 1)
            ->limit(1);

        return (bool)$connection->fetchOne($select);
    }

    /**
     * Get unpushed orders
     * @return array
     */
    public function getUnpushedOrders()
    {
        $connection = $this->getConnection();
        $addonPushedTable = $this->getTable('weltpixel_ga4_orders_addon_pushed');

        $select = $connection->select()
            ->from(['ap' => $addonPushedTable], ['order_id'])
            ->join(
                ['at' => $this->getMainTable()],
                'ap.id = at.pushed_id',
                ['addon_type']
            )
            ->where('ap.created_at < date_sub(NOW(), INTERVAL 2 MINUTE)')
            ->where('at.is_pushed = ?', 0);

        return $connection->fetchAll($select);
    }
}
