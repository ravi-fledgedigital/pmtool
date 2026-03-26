<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class ReturnRulesWebsitesCollection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Amasty\Rma\Model\ReturnRules\ReturnRulesWebsites::class,
            \Amasty\Rma\Model\ReturnRules\ResourceModel\ReturnRulesWebsites::class
        );
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }
}
