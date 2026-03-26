<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\ReturnRules;

use Amasty\Rma\Controller\Adminhtml\AbstractReturnRules;
use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractReturnRules
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Amasty_Rma::return_rules');
        $resultPage->addBreadcrumb(__('RMA'), __('RMA'));
        $resultPage->addBreadcrumb(__('Return Rules'), __('Return Rules'));
        $resultPage->getConfig()->getTitle()->prepend(__('Return Rules'));

        return $resultPage;
    }
}
