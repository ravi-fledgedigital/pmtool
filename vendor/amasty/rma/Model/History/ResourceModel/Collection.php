<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\History\ResourceModel;

use Amasty\Rma\Api\Data\HistoryInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init(
            \Amasty\Rma\Model\History\History::class,
            \Amasty\Rma\Model\History\ResourceModel\History::class
        );
        $this->_setIdFieldName($this->getResource()->getIdFieldName())
            ->addOrder(HistoryInterface::EVENT_ID);
    }

    public function addRequestFilter($requestId)
    {
        $this->addFieldToFilter(HistoryInterface::REQUEST_ID, (int)$requestId);

        return $this;
    }
}
