<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Rma;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;

class Manage extends \Magento\Backend\App\Action
{
    /**
     * @var StoreShipping
     */
    protected $storeShipping;

    /**
     * Index constructor.
     * @param Action\Context $context
     * @param StoreShipping $storeShipping
     */
    public function __construct(
        Action\Context $context,
        StoreShipping $storeShipping
    )
    {
        parent::__construct($context);
        $this->storeShipping = $storeShipping;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('OnitsukaTiger_NetSuiteStoreShipping::manage');
        $resultPage->addBreadcrumb(__('RMA'), __('RMA'));
        $resultPage->addBreadcrumb(__('Manage Requests'), __('Manage Requests'));

        $sourceInfo = $this->storeShipping->getSourcesDetails($this->getRequest()->getParam('manager_code'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Requests [%1]', $sourceInfo ? $sourceInfo->getName() : ''));

        return $resultPage;
    }
}
