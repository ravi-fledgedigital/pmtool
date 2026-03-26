<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Attribute;

use OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Attribute;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Indexer\IndexerRegistry;

class Index extends Attribute
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    public function __construct(
        Action\Context $context,
        IndexerRegistry $indexerRegistry
    ) {
        parent::__construct($context);
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $indexer = $this->indexerRegistry->get(\OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity::GRID_INDEXER_ID);
        if (!$indexer->isScheduled() && $indexer->isInvalid()) {
            $this->messageManager->addWarningMessage(__('Reindex \'Order Attributes Grid by OnitsukaTiger\' required.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('OnitsukaTiger_OrderAttribute::attributes_list');
        $resultPage->addBreadcrumb(__('Order Attribute'), __('Order Attribute'));
        $resultPage->addBreadcrumb(__('Manage Order Attributes'), __('Manage Order Attributes'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Order Attributes'));

        return $resultPage;
    }
}
