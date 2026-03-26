<?php

namespace OnitsukaTiger\CustomStoreLocator\Controller\Adminhtml\Grid;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use OnitsukaTiger\CustomStoreLocator\Model\ResourceModel\Grid\CollectionFactory;

class MassDelete extends Action{
    protected $_filter;
    protected $_collectionFactory;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ){
        parent::__construct($context);
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
    }

    public function execute(){
        $collection = $this->_filter->getCollection($this->_collectionFactory->create());
        $recordDeleted = 0;
        foreach ($collection->getItems() as $record) {
            // print_r($record);exit;
            $record->delete();
            $recordDeleted++;
        }
        $this->messageManager->addSuccessMessage(__('%1 record(s) have been deleted.', $recordDeleted));
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTiger_CustomStoreLocator::add_row');
    }
}
