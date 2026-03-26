<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Status\ResourceModel;

use Amasty\Rma\Api\Data\StatusInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Status extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_status';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, StatusInterface::STATUS_ID);
    }

    public function unsetPreviousInitialStatus($statusId)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [StatusInterface::IS_INITIAL => 0],
            [StatusInterface::STATUS_ID . ' <> ?' => (int)$statusId]
        );
    }

    public function unsetAutoEvent($autoEvent, $state, $statusId)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [StatusInterface::AUTO_EVENT => 0],
            [
                StatusInterface::STATUS_ID . ' <> ?' => (int)$statusId,
                StatusInterface::STATE . ' = ?' => (int)$state,
                StatusInterface::AUTO_EVENT . ' = ?' => (int)$autoEvent
            ]
        );
    }

    /**
     * @param $grid
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGridStatuses($grid)
    {
        $select = $this->getConnection()->select()->from(['statuses' => $this->getMainTable()])
            ->where('statuses.' . StatusInterface::GRID . ' = ?', (int)$grid)
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns('statuses.' . StatusInterface::STATUS_ID);

        if ($statusIds = $this->getConnection()->fetchCol($select)) {
            return $statusIds;
        }

        return [];
    }
}
