<?php

namespace OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\Reason;

use Magento\Backend\Model\View\Result\Page;
use OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\AbstractReason;
use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractReason
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('OnitsukaTigerKorea_OrderCancel::reason');
        $resultPage->addBreadcrumb(__('Order Cancel Reasons'), __('Order Cancel Reasons'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Order Cancel Reasons'));

        return $resultPage;
    }
}
