<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Relation;

use Magento\Framework\Controller\ResultFactory;

class Index extends \OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Relation
{
    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->setActiveMenu('OnitsukaTiger_OrderAttribute::attributes_relation');
        $resultPage->addBreadcrumb(__('Order Attribute'), __('Order Attribute'));
        $resultPage->addBreadcrumb(__('Attribute Relation'), __('Attribute Relation'));
        $resultPage->getConfig()->getTitle()->prepend(__('Order Attribute Relations'));

        return $resultPage;
    }
}
