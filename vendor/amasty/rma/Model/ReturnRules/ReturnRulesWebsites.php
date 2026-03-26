<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules;

use Amasty\Rma\Api\Data\ReturnRulesWebsitesInterface;
use Magento\Framework\Model\AbstractModel;

class ReturnRulesWebsites extends AbstractModel implements ReturnRulesWebsitesInterface
{
    protected function _construct()
    {
        $this->_init(\Amasty\Rma\Model\ReturnRules\ResourceModel\ReturnRulesWebsites::class);
        $this->setIdFieldName(ReturnRulesWebsitesInterface::RULE_WEBSITE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRuleWebsiteId($id)
    {
        return $this->setData(ReturnRulesWebsitesInterface::RULE_WEBSITE_ID, (int)$id);
    }

    /**
     * @inheritdoc
     */
    public function getRuleWebsiteId()
    {
        return (int)$this->_getData(ReturnRulesWebsitesInterface::RULE_WEBSITE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRuleId($id)
    {
        return $this->setData(ReturnRulesWebsitesInterface::RULE_ID, (int)$id);
    }

    /**
     * @inheritdoc
     */
    public function getRuleId()
    {
        return (int)$this->_getData(ReturnRulesWebsitesInterface::RULE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setWebsiteId($id)
    {
        return $this->setData(ReturnRulesWebsitesInterface::WEBSITE_ID, (int)$id);
    }

    /**
     * @inheritdoc
     */
    public function getWebsiteId()
    {
        return (int)$this->_getData(ReturnRulesWebsitesInterface::WEBSITE_ID);
    }
}
