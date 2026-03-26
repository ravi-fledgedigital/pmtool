<?php
namespace Cpss\Crm\Controller\Adminhtml\Receipt;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Controller\ResultFactory;
use Cpss\Crm\Model\ResourceModel\ShopReceipt\CollectionFactory;
use Cpss\Crm\Model\Shop\DeleteReceipt;

class MassReturn extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * @var Filter
     */
    protected $filter;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    /**
    * @var \Cpss\Crm\Model\Shop\DeleteReceipt
    */
    protected $deleteModel;
    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param DeleteReceipt $deleteModel
     */
    public function __construct(
        Context $context, 
        Filter $filter, 
        CollectionFactory $collectionFactory,
        DeleteReceipt $deleteModel
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->deleteModel = $deleteModel;
        parent::__construct($context);
    }
    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $successfulCounter = 0;
        foreach ($collection as $item) {
            try {
                $this->deleteModel->processDeletion($item->getPurchaseId());
                $successfulCounter++;
            }catch(\Exception $e){
                $this->messageManager->addErrorMessage(__('%1 was not deleted.', $item->getPurchaseId()));
            }
        }

        if($successfulCounter > 0){
            $this->messageManager->addSuccessMessage(__('%1 receipt(s) were deleted.', $successfulCounter));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}