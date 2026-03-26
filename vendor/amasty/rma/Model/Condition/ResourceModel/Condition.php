<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Condition\ResourceModel;

use Amasty\Rma\Api\Data\ConditionInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Condition extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_item_condition';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, ConditionInterface::CONDITION_ID);
    }
}
