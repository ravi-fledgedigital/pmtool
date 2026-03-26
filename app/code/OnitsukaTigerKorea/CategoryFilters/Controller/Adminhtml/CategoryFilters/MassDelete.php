<?php

namespace OnitsukaTigerKorea\CategoryFilters\Controller\Adminhtml\CategoryFilters;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use OnitsukaTigerKorea\CategoryFilters\Model\ResourceModel\CategoryFilters\CollectionFactory;

class MassDelete extends Action
{
    /**
     * @var $collectionFactory
     */
    public $collectionFactory;

    /**
     * @var $filter
     */
    public $filter;

    /**
     * @var $categoryFiltersFactory
     */
    public $categoryFiltersFactoy;

    /**
     * This is construct for delete
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param  \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactoy
     */

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactoy
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->categoryFiltersFactoy = $categoryFiltersFactoy;
        parent::__construct($context);
    }

    /**
     * This use for mass delete record
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());

            $count = 0;
            foreach ($collection as $model) {
                $model = $this->categoryFiltersFactoy->create()->load($model->getFilterId());
                $model->delete();
                $count++;
            }
            $this->messageManager->addSuccess(__('A total of %1 category Filter(s) have been deleted.', $count));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }

    /**
     * Authorization is allowed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTigerKorea_CategoryFilters::delete');
    }
}
