<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules;

use Amasty\Rma\Api\Data\ReturnRulesCustomerGroupsInterface;
use Magento\Framework\Model\AbstractModel;

class ReturnRulesCustomerGroups extends AbstractModel implements ReturnRulesCustomerGroupsInterface
{
    protected function _construct()
    {
        $this->_init(\Amasty\Rma\Model\ReturnRules\ResourceModel\ReturnRulesCustomerGroups::class);
        $this->setIdFieldName(ReturnRulesCustomerGroupsInterface::RULE_CUSTOMER_GROUP_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRuleCustomerGroupId($id)
    {
        return $this->setData(ReturnRulesCustomerGroupsInterface::RULE_CUSTOMER_GROUP_ID, (int)$id);
    }

    /**
     * @inheritdoc
     */
    public function getRuleCustomerGroupId()
    {
        return (int)$this->_getData(ReturnRulesCustomerGroupsInterface::RULE_CUSTOMER_GROUP_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRuleId($id)
    {
        return $this->setData(ReturnRulesCustomerGroupsInterface::RULE_ID, (int)$id);
    }

    /**
     * @inheritdoc
     */
    public function getRuleId()
    {
        return (int)$this->_getData(ReturnRulesCustomerGroupsInterface::RULE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerGroupId($id)
    {
        return $this->setData(ReturnRulesCustomerGroupsInterface::CUSTOMER_GROUP_ID, (int)$id);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerGroupId()
    {
        return (int)$this->_getData(ReturnRulesCustomerGroupsInterface::CUSTOMER_GROUP_ID);
    }
}
