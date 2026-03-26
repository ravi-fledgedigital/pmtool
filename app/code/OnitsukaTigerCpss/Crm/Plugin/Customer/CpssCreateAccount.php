<?php

namespace OnitsukaTigerCpss\Crm\Plugin\Customer;

use Cpss\Crm\Model\CpssApiRequest;
use Magento\Customer\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use OnitsukaTigerCpss\Crm\Helper\Data;

class CpssCreateAccount extends \Cpss\Crm\Plugin\Customer\CpssCreateAccount
{
    protected $customerSession;
    public function __construct(
        CpssApiRequest $cpssApiRequest,
        Session $customerSession,
        \Cpss\Crm\Helper\Data $helperData,
        Data $helperCustomer,
        ManagerInterface $message,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->customerSession = $customerSession;
        parent::__construct($cpssApiRequest, $customerSession, $helperData, $helperCustomer, $message, $urlBuilder);
    }

    public function createAccount()
    {
        try {
            if ($this->helperCustomer->enabled()) {
                $prefix = $this->helperCustomer->getCpssMembeIdPrefix();
                $memberId = $prefix . $this->customerSession->getCustomerId(); // . $this->helperData->getCountryCode();

                $response = $this->cpssApiRequest->getMemberStatus($memberId);
                $this->customerSession->setPointServiceEnabled(true);
                $this->helperCustomer->setMemberId($memberId);
                if ($response['http_code'] != 200) {
                    $this->customerSession->setPointServiceEnabled(false);
                    $this->displayErrorMessage();
                } elseif (isset($response['X-CPSS-Result']) && $response['X-CPSS-Result'] == '003-001-000') {
                    $addResponse = $this->cpssApiRequest->addMember($memberId);
                    if (isset($addResponse['X-CPSS-Result']) && $addResponse['X-CPSS-Result'] == '000-000-000') {
                        $response = json_decode($addResponse['Body'][0][0], true);
                        $this->helperCustomer->setMemberId($response['result']['aid']);
                    } else {
                        $this->customerSession->setPointServiceEnabled(false);
                        $this->displayErrorMessage();
                    }
                } elseif (isset($response['X-CPSS-Result']) && $response['X-CPSS-Result'] == '000-000-000') {
                    $result = json_decode($response['Body'][0][0], true);
                    $result = $result['result'];
                    $this->helperCustomer->setMemberId($result['aid']);
                } /*else {
                    $this->customerSession->setPointServiceEnabled(false);
                    $this->displayErrorMessage();
                }*/
            }
        } catch (\Exception $e) {
            $this->message->addErrorMessage($e->getMessage());
        }
    }
}
