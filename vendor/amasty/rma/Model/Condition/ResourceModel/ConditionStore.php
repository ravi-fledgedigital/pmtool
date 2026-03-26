<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Condition\ResourceModel;

use Amasty\Rma\Api\Data\ConditionStoreInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ConditionStore extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_item_condition_store';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, ConditionStoreInterface::CONDITION_STORE_ID);
    }
}
