<?php

namespace OnitsukaTigerCpss\Customer\Plugin\Customer\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Customer as BaseCustomer;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\UrlInterface;
use OnitsukaTigerCpss\Crm\Helper\Data;

/**
 *  custom Customer register
 */
class Customer
{
    /**
     * api V1/registerMember
     */
    public const URL_REGISTER_MEMBER = 'V1/registerMember';
    /**
     * @var UrlInterface
     */
    protected UrlInterface $url;
    /**
     * @var \OnitsukaTigerCpss\Customer\Plugin\Customer\Model\AccountManagement
     */
    protected AccountManagement  $accountManagement;
    /**
     * @var EmailNotificationInterface
     */
    protected EmailNotificationInterface $emailNotification;

    /**
     * @var Session
     */
    protected Session $session;
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;
    /**
     * @var Data
     */
    private Data $helperData;

    /**
     * @param UrlInterface $url
     * @param AccountManagement $accountManagement
     * @param EmailNotificationInterface $emailNotification
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $helperData
     */
    public function __construct(
        UrlInterface                $url,
        AccountManagement           $accountManagement,
        EmailNotificationInterface  $emailNotification,
        Session                     $customerSession,
        CustomerRepositoryInterface $customerRepository,
        Data                        $helperData
    ) {
        $this->url = $url;
        $this->emailNotification = $emailNotification;
        $this->accountManagement = $accountManagement;
        $this->session = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->helperData = $helperData;
    }

    /**
     * @param BaseCustomer $customer
     * @param \Closure $proceed
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     * @return array|string[]|void
     */
    public function aroundSendNewAccountEmail(
        BaseCustomer $customer,
        \Closure     $proceed,
        $type = 'registered',
        $backUrl = '',
        $storeId = '0'
    ) {
        try {
            $requestUrl = $this->url->getCurrentUrl();
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/login.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Login Model Customer Session Data: ' . $this->session->getIsRedirectAppLogin());

            if (strpos($requestUrl, 'V1/registerMember')
                || strpos($requestUrl, 'registeredfromapp')
                || $this->session->getIsRedirectAppLogin()) {
                $clientIds = explode(',', $this->helperData->getLoginClientId());
                $clientId = $clientIds[0];
                $templateType = AccountManagement::NEW_ACCOUNT_EMAIL_CONFIRMATION;
                $query = '';
                if (strpos($requestUrl, 'V1/registerMember')) {
                    $query = $this->url->getUrl('customer/account/index', ['_secure' => true]);
                    $query .= '&' . $this->helperData->buildConfirmQuery($clientId);
                }
                $customer = $this->customerRepository->getById($customer->getId());
                $this->emailNotification->newAccount($customer, $templateType, $query, $customer->getStoreId());
            } else {
                $result = $proceed($type, $backUrl, $storeId);
                return $result;
            }
        } catch (\Exception $exception) {
        }
    }
}
