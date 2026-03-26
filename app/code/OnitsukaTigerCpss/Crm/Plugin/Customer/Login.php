<?php

namespace OnitsukaTigerCpss\Crm\Plugin\Customer;

use Cpss\Crm\Plugin\Customer\CpssCreateAccount;
use Magento\Customer\Controller\Account\LoginPost;
use Magento\Framework\UrlInterface;

/**
 * Class Customer Login
 */
class Login extends CpssCreateAccount
{
    /**
     * @var \OnitsukaTigerCpss\Crm\Helper\Data
     */
    private $cpssHelper;

    /**
     * @param \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Cpss\Crm\Helper\Data $helperData
     * @param \Cpss\Crm\Helper\Customer $helperCustomer
     * @param \Magento\Framework\Message\ManagerInterface $message
     * @param UrlInterface $urlBuilder
     * @param \OnitsukaTigerCpss\Crm\Helper\Data $cpssHelper
     */
    public function __construct(
        \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest,
        \Magento\Customer\Model\Session $customerSession,
        \Cpss\Crm\Helper\Data $helperData,
        \Cpss\Crm\Helper\Customer $helperCustomer,
        \Magento\Framework\Message\ManagerInterface $message,
        \Magento\Framework\UrlInterface $urlBuilder,
        \OnitsukaTigerCpss\Crm\Helper\Data $cpssHelper,
        private \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
    ) {
        parent::__construct($cpssApiRequest, $customerSession, $helperData, $helperCustomer, $message, $urlBuilder);
        $this->cpssHelper = $cpssHelper;
    }

    /**
     * Create member when login customer
     *
     * @param LoginPost $subject
     * @param Closure $proceed
     * @return mixed
     */
    public function afterExecute(LoginPost $subject, $proceed)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $customer = $this->customerSession->getCustomer();
        $logger->info('Is customer loggin?: ' . $this->customerSession->isLoggedIn());
        $logger->info('Customer Website ID: ' . $customer->getWebsiteId());
        $logger->info('Customer Store ID: ' . $customer->getStoreId());
        $logger->info('Is customer agreed?: ' . $customer->getData('is_agreed'));
        if ($this->customerSession->isLoggedIn() && $customer->getStoreId() != 5) {
            $logger->info('Inside the create account condition');
            $logger->info('===================================');
            $this->createAccount();// disable current version
        } elseif ($customer->getStoreId() == 5 && $customer->getData('is_agreed') == 1) {
            $prefix = $this->helperData->getCpssMembeIdPrefix();
            $memberId = $prefix . $this->customerSession->getCustomerId();
            $this->customerSession->setPointServiceEnabled(true);
            $this->helperCustomer->setMemberId($memberId);
        }

        $logger->info('Login Form Session Data: ' . $this->customerSession->getIsRedirectAppLogin());
        if ($this->customerSession->getIsRedirectAppLogin()) {
            $url = $this->urlBuilder->getUrl('customer/account/index');
            $url .= '&' . $this->cpssHelper->buildQuery($customer->getId());
            /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
            $redirect = $this->resultRedirectFactory->create();
            $redirect->setUrl($url);
            return $redirect;
        }
        return $proceed;
    }
}
