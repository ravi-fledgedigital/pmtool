<?php

namespace Cpss\Crm\Plugin\Customer;

class CpssCreateAccount
{
    /**
     * @var \Cpss\Crm\Model\CpssApiRequest
     */
    protected $cpssApiRequest;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Cpss\Crm\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Cpss\Crm\Helper\Customer
     */
    protected $helperCustomer;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $message;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    public function __construct(
        \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest,
        \Magento\Customer\Model\Session $customerSession,
        \Cpss\Crm\Helper\Data $helperData,
        \Cpss\Crm\Helper\Customer $helperCustomer,
        \Magento\Framework\Message\ManagerInterface $message,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->cpssApiRequest = $cpssApiRequest;
        $this->customerSession = $customerSession;
        $this->helperData = $helperData;
        $this->helperCustomer = $helperCustomer;
        $this->message = $message;
        $this->urlBuilder = $urlBuilder;
    }

    public function createAccount()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/registerSuccess.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Register Success Debugging Start============================');

        try {
            if ($this->helperData->enabled()) {
                $prefix = $this->helperData->getCpssMembeIdPrefix();
                $memberId = $prefix . $this->customerSession->getCustomerId(); // . $this->helperData->getCountryCode();
                $logger->info('Prefix: ' . $prefix);
                $logger->info('Member ID: ' . $memberId);
                $response = $this->cpssApiRequest->getMemberStatus($memberId);
                $logger->info('Response: ' . print_r($response, true));
                $this->customerSession->setPointServiceEnabled(true);
                $this->helperCustomer->setMemberId($memberId);
                if ($response['http_code'] != 200) {
                    $this->customerSession->setPointServiceEnabled(false);
                    $this->displayErrorMessage();
                } elseif (isset($response['X-CPSS-Result']) && $response['X-CPSS-Result'] == '003-001-000') {
                    $addResponse = $this->cpssApiRequest->addMember($memberId);
                    $logger->info('Response add member: ' . print_r($addResponse, true));
                    if (isset($addResponse['X-CPSS-Result']) && $addResponse['X-CPSS-Result'] == '000-000-000') {
                        $response = json_decode($addResponse['Body'][0][0], true);
                        $logger->info('Response After member add: ' . print_r($response, true));
                        $this->helperCustomer->setMemberId($response['result']['aid']);
                    } else {
                        $this->customerSession->setPointServiceEnabled(false);
                        $this->displayErrorMessage();
                    }
                } elseif (isset($response['X-CPSS-Result']) && $response['X-CPSS-Result'] == '000-000-000') {
                    $result = json_decode($response['Body'][0][0], true);
                    $result = $result['result'];
                    $logger->info('Response Result: ' . print_r($result, true));
                    $this->helperCustomer->setMemberId($result['aid']);
                } else {
                    $this->customerSession->setPointServiceEnabled(false);
                    $this->displayErrorMessage();
                }
            }
        } catch (\Exception $e) {
            $this->message->addErrorMessage($e->getMessage());
        }

        $logger->info('==========================Register Success Debugging End============================');
    }

    public function displayErrorMessage()
    {
        $this->message->addErrorMessage(__('Point service integration failed. The point service is currently unavailable. Sorry to trouble you, but please log in again.'));
    }
}
