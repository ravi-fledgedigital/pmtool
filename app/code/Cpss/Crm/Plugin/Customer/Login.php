<?php

namespace Cpss\Crm\Plugin\Customer;

use Cpss\Crm\Model\CpssApiRequest;
use Magento\Customer\Controller\Account\LoginPost;
use Magento\Customer\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use OnitsukaTigerCpss\Crm\Helper\Data;

class Login extends CpssCreateAccount
{
    public function afterExecute(\Magento\Customer\Controller\Account\LoginPost $subject, $proceed)
    {
        if ($this->customerSession->isLoggedIn()) {
            $this->createAccount();// disable current version
        }
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Login Form Session Data: ' . $this->customerSession->getIsRedirectAppLogin());
        if ($this->customerSession->getIsRedirectAppLogin()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $crmHelper = $objectManager->create(\OnitsukaTigerCpss\Crm\Helper\Data::class);
            $url = $crmHelper->getAppRedirectUrl($this->customerSession->getCustomer()->getId());
            $logger->info('CustomerID: ' . $this->customerSession->getCustomer()->getId());
            $logger->info('URL: ' . $url);
            //$url = $this->urlBuilder->getUrl('customer/account/index');
            $proceed->setUrl($url);
            return $proceed;
        }
        return $proceed;
    }
}
