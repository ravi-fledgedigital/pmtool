<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Condition;

use Amasty\Rma\Controller\Adminhtml\AbstractCondition;
use Magento\Framework\Controller\ResultFactory;

class Create extends AbstractCondition
{
    /**
     * @return void
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);

        return $result->forward('edit');
    }
}
