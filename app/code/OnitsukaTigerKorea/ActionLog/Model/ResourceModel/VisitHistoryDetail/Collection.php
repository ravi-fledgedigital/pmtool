<?php

namespace OnitsukaTigerKorea\ActionLog\Model\ResourceModel\VisitHistoryDetail;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     *
     * @var type
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
            $this->_init(\Amasty\AdminActionsLog\Model\VisitHistoryEntry\VisitHistoryDetail::class, \Amasty\AdminActionsLog\Model\VisitHistoryEntry\ResourceModel\VisitHistoryDetail::class);
            $this->_map['fields']['id'] = 'main_table.id';
    }
}
