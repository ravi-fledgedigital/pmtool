<?php

namespace OnitsukaTiger\OrderTracking\Controller\Guest;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Helper\Guest as GuestHelper;

class Form extends \Magento\Sales\Controller\Guest\Form {

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var CustomerSession|null
     */
    private $customerSession;

    /**
     * @var GuestHelper|null
     */
    private $guestHelper;

    /**
     * Form constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerSession|null $customerSession
     * @param GuestHelper|null $guestHelper
     */
    public function __construct(Context $context, PageFactory $resultPageFactory, CustomerSession $customerSession = null, GuestHelper $guestHelper = null)
    {
        parent::__construct($context, $resultPageFactory, $customerSession, $guestHelper);
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession ?: ObjectManager::getInstance()->get(CustomerSession::class);
        $this->guestHelper = $guestHelper ?: ObjectManager::getInstance()->get(GuestHelper::class);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Track Your Order'));
        $this->guestHelper->getBreadcrumbs($resultPage);

        return $resultPage;
    }
}