<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules;

use Amasty\Rma\Api\Data\ReturnRulesResolutionsInterface;
use Magento\Framework\Model\AbstractModel;

class ReturnRulesResolutions extends AbstractModel implements ReturnRulesResolutionsInterface
{
    protected function _construct()
    {
        $this->_init(\Amasty\Rma\Model\ReturnRules\ResourceModel\ReturnRulesResolutions::class);
        $this->setIdFieldName(ReturnRulesResolutionsInterface::RULE_RESOLUTION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRuleResolutionId($id)
    {
        return $this->setData(ReturnRulesResolutionsInterface::RULE_RESOLUTION_ID, (int)$id);
    }

    /**
     * @inheritdoc
     */
    public function getRuleResolutionId()
    {
        return (int)$this->_getData(ReturnRulesResolutionsInterface::RULE_RESOLUTION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRuleId($id)
    {
        return $this->setData(ReturnRulesResolutionsInterface::RULE_ID, (int)$id);
    }

    /**
     * @inheritdoc
     */
    public function getRuleId()
    {
        return (int)$this->_getData(ReturnRulesResolutionsInterface::RULE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setResolutionId($id)
    {
        return $this->setData(ReturnRulesResolutionsInterface::RESOLUTION_ID, (int)$id);
    }

    /**
     * @inheritdoc
     */
    public function getResolutionId()
    {
        return (int)$this->_getData(ReturnRulesResolutionsInterface::RESOLUTION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setValue($value)
    {
        return $this->setData(ReturnRulesResolutionsInterface::VALUE, $value);
    }

    /**
     * @inheritdoc
     */
    public function getValue()
    {
        return $this->_getData(ReturnRulesResolutionsInterface::VALUE);
    }
}
