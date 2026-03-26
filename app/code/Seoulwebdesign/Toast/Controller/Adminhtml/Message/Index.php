<?php

namespace Seoulwebdesign\Toast\Controller\Adminhtml\Message;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var bool|PageFactory
     */
    protected $resultPageFactory = false;

    /**
     * The constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Main execute
     *
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Seoulwebdesign_Toast::toast');
        $resultPage->addBreadcrumb(__('Seoulwebdesign Toast'), __('Seoulwebdesign Toast'));
        $resultPage->addBreadcrumb(__('Manage Toast Message'), __('Manage Toast Message'));
        $resultPage->getConfig()->getTitle()->prepend((__('Messages')));

        return $resultPage;
    }

    /**
     * Check is allow
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Seoulwebdesign_Toast::message');
    }
}
