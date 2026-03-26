<?php

namespace OnitsukaTiger\CustomStoreLocator\Controller\Adminhtml\Grid;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action{
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ){
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('OnitsukaTiger_CustomStoreLocator::manager');
        $resultPage->getConfig()->getTitle()->prepend(__('Store Locator Data'));
        return $resultPage;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTiger_CustomStoreLocator::add_row');
    }
}
