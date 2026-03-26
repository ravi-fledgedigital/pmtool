<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Reason\ResourceModel;

use Amasty\Rma\Api\Data\ReasonInterface;
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
            \Amasty\Rma\Model\Reason\Reason::class,
            \Amasty\Rma\Model\Reason\ResourceModel\Reason::class
        );
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }

    /**
     * @return Collection
     */
    public function addNotDeletedFilter()
    {
        return $this->addFieldToFilter(ReasonInterface::IS_DELETED, 0);
    }
}
