<?php
/**
 * Copyright © OnitsukaTiger All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerCpss\Crm\Controller\RealStore;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use OnitsukaTigerCpss\Crm\Helper\Data as HelperData;
use OnitsukaTigerCpss\Crm\Helper\HelperData as OnitsukaTigerCpssHelperData;

class Purchase implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var RedirectFactory
     */
    protected $redirect;

    protected $helperData;

    /**
     * @var OnitsukaTigerCpssHelperData
     */
    protected $cpssHelper;

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Session $customerSession,
        RedirectFactory $redirect,
        HelperData $helperData,
        OnitsukaTigerCpssHelperData $cpssHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->redirect = $redirect;
        $this->helperData = $helperData;
        $this->cpssHelper = $cpssHelper;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $_pageFactory = $this->resultPageFactory->create();
        $_pageFactory->getConfig()->getTitle()->set(__("Onitsuka Tiger Official Online Store | Store Purchase"));
        $_pageFactory->getLayout()->getBlock('page.main.title')->setPageTitle(__("Store Purchase"), "Store Purchase");

        if (!$this->helperData->isModuleEnabled() || !$this->cpssHelper->getShowStorePurchase()) {
            $resultRedirect = $this->redirect->create();
            return $resultRedirect->setPath('customer/account');
        }
        return $_pageFactory;
    }
}
