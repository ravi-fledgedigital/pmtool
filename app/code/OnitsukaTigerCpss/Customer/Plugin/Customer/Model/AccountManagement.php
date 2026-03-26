<?php

namespace OnitsukaTigerCpss\Customer\Plugin\Customer\Model;

use Magento\Customer\Model\Session;
use Magento\Framework\UrlInterface;
use OnitsukaTigerCpss\Crm\Helper\CrmData;
use OnitsukaTigerCpss\Crm\Helper\Data as DataHelper;

class AccountManagement
{
    /**
    * @var DataHelper
    */
    private $helperData;
    /**
     * @var CrmData
     */
    private $helperCustomer;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    protected $session;
    public function __construct(
        Session $customerSession,
        DataHelper $helperData,
        CrmData $helperCustomer,
        UrlInterface $urlBuilder
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->session = $customerSession;
        $this->helperData = $helperData;
        $this->helperCustomer = $helperCustomer;
    }

    public function beforeCreateAccount(
        \Magento\Customer\Model\AccountManagement $subject,
        $customer,
        $password = null,
        $redirectUrl = null
    ) {
        try {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/login.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Login Account Management Form Session Data: ' . $this->session->getIsRedirectAppLogin());

            if ($this->session->getIsRedirectAppLogin()) {
                $redirectUrl = empty($redirectUrl) ? $this->_urlBuilder->getUrl('customer/account/index') : $redirectUrl;
                $logger->info('Redirect URL: ' . $redirectUrl);
                $redirectUrl =  $redirectUrl . '&' . $this->buildParams();
                $logger->info('Redirect With Build Query: ' . $redirectUrl);
                $logger->info('Login Account Management Form Session Data: ' . $this->_urlBuilder->getUrl('customer/account/index'));
                return [$customer, $password, $redirectUrl];
            }
        } catch (\Exception $exception) {
        }
        return [$customer, $password, $redirectUrl];
    }
    private function buildParams()
    {
        $requestApplogin = $this->session->getAppLoginRequest();
        $query = "app_id=" . ($requestApplogin['app_id'] ?? '');
        $query .= "&client_id=" . ($requestApplogin['client_id'] ?? '');
        $query .= "&redirect_to=" . ($requestApplogin['redirect_to'] ?? '');
        $query  .='&' . DataHelper::PARAMS_REGISTERED_FROM_APP . '=true';
        return $query;
    }
}
