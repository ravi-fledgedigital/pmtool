<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\ActionLog\Controller\Adminhtml\Pageview;

use Magento\Backend\App\Action;

class Index extends Action
{
    protected $resultPageFactory = false;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('Page View History')));

        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTigerKorea_ActionLog::page_view_history');
    }
}
