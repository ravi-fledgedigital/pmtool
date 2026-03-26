<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request\ResourceModel;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Model\Status\ResourceModel\Status;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Request extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_request';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, RequestInterface::REQUEST_ID);
    }

    public function getRequestIdByHash($hash)
    {
        $select = $this->getConnection()->select()->from(['request' => $this->getMainTable()])
            ->where('request.' . RequestInterface::URL_HASH . ' = ?', $hash)
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns('request.' . RequestInterface::REQUEST_ID);

        if ($requestId = $this->getConnection()->fetchOne($select)) {
            return (int)$requestId;
        }

        return false;
    }

    public function getRequestCountByStatuses($statuses)
    {
        if (empty($statuses)) {
            return 0;
        }

        $select = $this->getConnection()->select()
            ->from(['request' => $this->getMainTable()], new \Zend_Db_Expr('count(*)'))
            ->joinInner(['sales_order' => $this->getTable('sales_order')], 'sales_order.entity_id = request.order_id')
            ->where('request.' . RequestInterface::STATUS . ' IN (?)', $statuses);

        return (int)$this->getConnection()->fetchOne($select);
    }

    public function getTotalByState()
    {
        $select = $this->getConnection()->select()->from(['main_table' => $this->getMainTable()])
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(['total' => new \Zend_Db_Expr('count(*)')])
            ->joinInner(
                ['status_table' => $this->getTable(Status::TABLE_NAME)],
                'main_table.status = status_table.status_id AND status_table.state IN (0, 1, 2)',
                ['status_table.state']
            )->group('status_table.state');

        $result = [];
        if ($rows = $this->getConnection()->fetchAll($select)) {
            foreach ($rows as $row) {
                $result[$row['state']] = $row['total'];
            }
        }

        return $result;
    }

    public function getManagerRequestsCount()
    {
        $select = $this->getConnection()->select()->from(['main_table' => $this->getMainTable()])
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(['total' => 'count(*)', 'main_table.manager_id'])
            ->joinInner(
                ['status_table' => $this->getTable(Status::TABLE_NAME)],
                'main_table.status = status_table.status_id AND status_table.state IN (0, 1, 2)',
                ['status_table.state']
            )->group('main_table.manager_id');

        $result = [];
        if ($rows = $this->getConnection()->fetchAll($select)) {
            foreach ($rows as $row) {
                $result[$row['manager_id']] = $row['total'];
            }
        }

        return $result;
    }
}
