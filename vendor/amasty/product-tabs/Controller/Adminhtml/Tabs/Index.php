<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Controller\Adminhtml\Tabs;

use Amasty\CustomTabs\Controller\Adminhtml\Tabs;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Index
 */
class Index extends Tabs
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Amasty_CustomTabs::tabs');
        $resultPage->addBreadcrumb(__('Amasty Advanced Tabs'), __('Amasty Advanced Tabs'));
        $resultPage->addBreadcrumb(__('Tab Management'), __('Tab Management'));
        $resultPage->getConfig()->getTitle()->prepend(__('Tab Management'));

        return $resultPage;
    }
}
