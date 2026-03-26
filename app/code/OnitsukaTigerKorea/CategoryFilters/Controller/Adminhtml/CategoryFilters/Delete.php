<?php

namespace OnitsukaTigerKorea\CategoryFilters\Controller\Adminhtml\CategoryFilters;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    /**
     * @var categoryFiltersFactory
     */
    public $categoryFiltersFactory;
    
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactory
     */
    public function __construct(
        Context $context,
        \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactory
    ) {
        $this->categoryFiltersFactory = $categoryFiltersFactory;
        parent::__construct($context);
    }

    /**
     * Delete record of category filter
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('filter_id');
        try {
            $categoryFiltersModel = $this->categoryFiltersFactory->create();
            $categoryFiltersModel->load($id);
            $categoryFiltersModel->delete();
            $this->messageManager->addSuccessMessage(__('You deleted Filter data.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $resultRedirect->setPath('*/*/index');
    }

    /**
     * Authorization is allowed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTigerKorea_CategoryFilters::delete');
    }
}
