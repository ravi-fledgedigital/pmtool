<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request\ResourceModel;

use Amasty\Rma\Api\Data\RequestItemInterface;
use Amasty\Rma\Model\Reason\ResourceModel\Reason;
use Amasty\Rma\Model\Status\ResourceModel\Status;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RequestItem extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_request_item';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, RequestItemInterface::REQUEST_ITEM_ID);
    }

    public function removeDeletedItems($requestId, $requestItemIds)
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            [
                RequestItemInterface::REQUEST_ID . ' = ?' => (int)$requestId,
                RequestItemInterface::REQUEST_ITEM_ID . ' NOT IN (?)' => $requestItemIds
            ]
        );
    }

    public function getNotArchivedItemsSelect()
    {
        return $this->getConnection()->select()->from(['main_table' => $this->getMainTable()])
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->joinInner(
                ['request_table' => $this->getTable(Request::TABLE_NAME)],
                'main_table.request_id = request_table.request_id',
                []
            )->joinInner(
                ['status_table' => $this->getTable(Status::TABLE_NAME)],
                'request_table.status = status_table.status_id AND status_table.state IN (0, 1, 2)',
                []
            );
    }

    public function getTop5Reasons()
    {
        $select = $this->getNotArchivedItemsSelect()->columns(['total' => 'count(*)'])->joinLeft(
            ['reason_table' => $this->getTable(Reason::TABLE_NAME)],
            'reason_table.reason_id = main_table.reason_id',
            ['reason_table.title']
        )->group('main_table.reason_id')->order('total DESC')->limit(5);

        $result = [];
        if ($rows = $this->getConnection()->fetchAll($select)) {
            return $rows;
        }

        return $result;
    }

    public function getReturnItemsBasePrice()
    {
        $select = $this->getNotArchivedItemsSelect()->columns(
            ['SUM(COALESCE(configurable_order_items.base_price, order_items.base_price)*main_table.qty)']
        )->joinLeft(
            ['order_items' => $this->getTable('sales_order_item')],
            'order_items.item_id = main_table.order_item_id',
            []
        )->joinLeft(
            ['configurable_order_items' => $this->getTable('sales_order_item')],
            'configurable_order_items.item_id = order_items.parent_item_id',
            []
        );

        if ($result = $this->getConnection()->fetchCol($select)) {
            return (double)$result[0];
        }

        return 0;
    }

    /**
     * @return array ['order_item_id' => 'request_qty']
     */
    public function getItemsQtyByRequestId(int $requestId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['order_item_id', 'request_qty'])
            ->where('request_id = ?', $requestId);

        return $connection->fetchPairs($select);
    }
}
