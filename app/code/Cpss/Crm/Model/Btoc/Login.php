<?php

namespace Cpss\Crm\Model\Btoc;

use Magento\Customer\Api\AccountManagementInterface;
use \Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Model\AddressFactory;
use Cpss\Crm\Model\Btoc\Config\Result;
use Cpss\Crm\Model\Btoc\Config\Param;
use Cpss\Crm\Helper\MemberValidation;
use Magento\Customer\Model\Session;
use Cpss\Crm\Helper\Customer;
use Cpss\Crm\Helper\Data;

class Login implements \Cpss\Crm\Api\Btoc\LoginInterface
{
    protected $addressFactory;
    protected $customerSession;
    protected $crmHelper;
    protected $subscriberFactory;
    protected $customerAccountManagement;
    protected $validation;
    protected $helperData;

    public function __construct(
        AddressFactory $addressFactory,
        Session $customerSession,
        Customer $crmHelper,
        SubscriberFactory $subscriberFactory,
        AccountManagementInterface $customerAccountManagement,
        MemberValidation $validation,
        Data $helperData
    ) {
        $this->addressFactory = $addressFactory;
        $this->customerSession = $customerSession;
        $this->crmHelper = $crmHelper;
        $this->subscriberFactory = $subscriberFactory;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->validation = $validation;
        $this->helperData = $helperData;
    }

    public function loginMember()
    {
        header('Content-Type: application/json');
        $result = [];
        $additional = [];
        try {
            $success = Result::SUCCESS;
            $params = $this->crmHelper->getParamsRequest();
            $this->crmHelper->logDebug("LOGIN", $params);
            $resultCode = $this->validation->validateParams(Param::LOGIN_MEMBER_PARAMS, $params);

            if ($resultCode === $success) {
                $response = $this->processLogin($params);
                $resultCode = $response['resultCode'];
                if (isset($response['additional'])) {
                    $additional = $response['additional'];
                }
            }

            $resultExplanation = Result::RESULT_CODES[$resultCode];
            $result = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ];
            $result = array_merge($result, $additional);
        } catch (\Exception $e) {
            $this->crmHelper->logCritical($e->getMessage());
            $resultCode = Result::INTERNAL_ERROR;
            $resultExplanation = Result::RESULT_CODES[$resultCode];
            $result = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ];
        }
        echo json_encode($result);
        exit();
    }

    public function processLogin($params)
    {
        if ((!isset($params['email']) && !isset($params['memberId'])) || (empty($params['email']) && empty($params['memberId']))) {
            $this->crmHelper->logCritical(__('At least 1 of the following parameter is required: "%1" or "%2"', Param::EMAIL, Param::MEMBER_ID));
            return ['resultCode' => Result::INVALID_PARAMS];
        }
        $email = isset($params['email']) ? $params['email'] : "";
        $password = $params['password'];
        $websiteId  = $this->crmHelper->getWebsiteId();
        $customer   = $this->crmHelper->getCustomerFactory();
        $customer->setWebsiteId($websiteId);
        if (empty($email)) {
            $email = $customer->load($params['memberId'])->getEmail();
        }

        try {
            $customer = $this->customerAccountManagement->authenticate($email, $password);
            $loggedInCustomer = $this->crmHelper->getCustomerFactory()->load($customer->getId());
            $hashedPassword = $loggedInCustomer->getPasswordHash();
            $accessToken = $this->crmHelper->generateAccessToken($hashedPassword);
            $this->customerSession->setCustomerDataAsLoggedIn($customer);
            $this->customerSession->regenerateId();

            if ($this->customerSession->isLoggedIn()) {
                $memberId = $this->crmHelper->getCpssMembeIdPrefix() . $customer->getId();
                $this->crmHelper->setMemberId($memberId);

                return $response = [
                    'resultCode' => Result::SUCCESS,
                    'additional' => [
                        'memberId' => $this->customerSession->getId(), // FOR REVISION
                        'countryCode' => $this->helperData->getCountryCode(),
                        'accessToken' => $accessToken
                    ]
                ];
            }
            return ['resultCode' => Result::AUTH_FAILED];
        } catch (\Magento\Framework\Exception\InvalidEmailOrPasswordException $e) {
            $this->crmHelper->logCritical($e->getMessage());
            return ['resultCode' => Result::AUTH_FAILED];
        } catch (\Magento\Framework\Exception\State\UserLockedException $e) {
            $this->crmHelper->logCritical($e->getMessage());
            return ['resultCode' => Result::ACCOUNT_LOCKED];
        } catch (\Exception $e) {
            $this->crmHelper->logCritical($e->getMessage());
            return ['resultCode' => Result::INTERNAL_ERROR];
        }
    }
}
