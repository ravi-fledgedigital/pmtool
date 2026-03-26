<?php

namespace OnitsukaTigerIndo\RmaAccount\Controller\Adminhtml\Items;

class Index extends \OnitsukaTigerIndo\RmaAccount\Controller\Adminhtml\Items
{
    /**
     * Items list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('OnitsukaTigerIndo_RmaAccount::test');
        $resultPage->getConfig()->getTitle()->prepend(__('RMA Account Details'));
        $resultPage->addBreadcrumb(__('RMA Account Details'), __('RMA Account Details'));
        $resultPage->addBreadcrumb(__('RMA Account Details Form'), __('RMA Account Details Form'));
        return $resultPage;
    }
}
