<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Status\ResourceModel;

use Amasty\Rma\Api\Data\StatusInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(
            \Amasty\Rma\Model\Status\Status::class,
            \Amasty\Rma\Model\Status\ResourceModel\Status::class
        );
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }

    /**
     * @return Collection
     */
    public function addNotDeletedFilter()
    {
        return $this->addFieldToFilter(StatusInterface::IS_DELETED, 0);
    }
}
