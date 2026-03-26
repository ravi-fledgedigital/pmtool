<?php

namespace OnitsukaTigerKorea\OrderCancel\Model\Reason\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class ReasonStoreCollection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(
            \OnitsukaTigerKorea\OrderCancel\Model\Reason\ReasonStore::class,
            ReasonStore::class
        );
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }
}
