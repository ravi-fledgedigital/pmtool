<?php
declare(strict_types=1);

namespace OnitsukaTiger\Restock\Controller\Adminhtml\Restock;

class View extends \Magento\Backend\App\Action
{

    protected $resultPageFactory;
    protected $coreSession;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Session\SessionManagerInterface $coreSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->coreSession = $coreSession;
        parent::__construct($context);
    }

    /**
     * View action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {   
        $requests = $this->getRequest()->getParams();
        $resultPage = $this->resultPageFactory->create();
        // $name = str_replace('~', ' ', $requests['name']);
        $name = 'View';
        $resultPage->getConfig()->getTitle()->prepend(__($name));
        $this->coreSession->setRestockProductId($requests['id']);
        return $resultPage;
    }
}
