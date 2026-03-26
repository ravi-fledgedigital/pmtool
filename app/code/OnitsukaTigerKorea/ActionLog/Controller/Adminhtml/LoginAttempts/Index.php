<?php

declare(strict_types=1);

namespace OnitsukaTigerKorea\ActionLog\Controller\Adminhtml\LoginAttempts;

use OnitsukaTigerKorea\ActionLog\Controller\Adminhtml\AbstractLoginAttempts;
use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractLoginAttempts
{
    /**
     * Execute Function
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('OnitsukaTigerKorea_ActionLog::amaudit');
        $resultPage->addBreadcrumb(__('Login Attempts'), __('Login Attempts'));
        $resultPage->getConfig()->getTitle()->prepend(__('Login Attempts'));

        return $resultPage;
    }
}
