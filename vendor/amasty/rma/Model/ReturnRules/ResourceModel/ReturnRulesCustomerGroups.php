<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules\ResourceModel;

use Amasty\Rma\Api\Data\ReturnRulesCustomerGroupsInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ReturnRulesCustomerGroups extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_return_rules_customer_groups';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, ReturnRulesCustomerGroupsInterface::RULE_CUSTOMER_GROUP_ID);
    }
}
