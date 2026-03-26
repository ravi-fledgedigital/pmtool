<?php

namespace OnitsukaTigerCpss\Crm\Model\Btoc;

use Cpss\Crm\Helper\Customer;
use Cpss\Crm\Helper\Data;
use Cpss\Crm\Model\Btoc\Config\Result;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\App\Emulation;
use OnitsukaTigerCpss\Crm\Helper\Data as CustomerHelper;
use OnitsukaTigerCpss\Crm\Helper\MemberValidation;
use OnitsukaTigerCpss\Crm\Model\Btoc\Config\Param;

class Login extends \Cpss\Crm\Model\Btoc\Login
{
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var CustomerHelper
     */
    private $customerHelper;
    protected $validation;

    public function __construct(
        AddressFactory $addressFactory,
        Session $customerSession,
        Customer $crmHelper,
        SubscriberFactory $subscriberFactory,
        AccountManagementInterface $customerAccountManagement,
        MemberValidation $validation,
        Data $helperData,
        Emulation $emulation,
        CustomerHelper $customerHelper
    )
    {
        $this->emulation = $emulation;
        $this->customerHelper = $customerHelper;
        $this->validation = $validation;
        parent::__construct(
            $addressFactory,
            $customerSession,
            $crmHelper,
            $subscriberFactory,
            $customerAccountManagement,
            $validation,
            $helperData);
    }


    public function loginMember()
    {
        header('Content-Type: application/json');
        $additional = [];
        try {
            $success = Result::SUCCESS;
            $params = $this->crmHelper->getParamsRequest();
            $this->crmHelper->logDebug("LOGIN", $params);
            $resultCode = $this->validation->validateData(Param::REQUEST_LOGIN_MEMBER_PARAMS, $params);
            if (!$this->crmHelper->validateAppLoginCredentials($params)) {
                $resultCode = Result::AUTH_FAILED;
            }
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
        if (!$this->validateParams($params)) {
            $this->crmHelper->logCritical(__('At least 1 of the following parameter is required: "%1" or "%2"', Param::EMAIL, Param::MEMBER_ID));
            return ['resultCode' => Result::INVALID_PARAMS];
        }
        $email = isset($params['email']) ? $params['email'] : "";
        $password = $params['password'];
        $websiteId  = $params[Param::SITE_ID];

        // Store Id
        $storeId = $this->customerHelper->getStoreDefaultId($websiteId);
        $this->emulation->startEnvironmentEmulation($storeId);

        if (empty($email)) {
            $customer  = $this->crmHelper->getCustomerFactory();
            $email = $customer->load($params['memberId'])->getData('email');
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
                        'countryCode' => $this->customerHelper->getCountryCode(),
                        'accessToken' => $accessToken
                    ]
                ];
            }

            $this->emulation->stopEnvironmentEmulation();
            return ['resultCode' => Result::AUTH_FAILED];
        } catch (\Magento\Framework\Exception\InvalidEmailOrPasswordException $e) {
            $this->crmHelper->logCritical($e->getMessage());
            return ['resultCode' => Result::AUTH_FAILED];
        } catch (\Magento\Framework\Exception\State\UserLockedException $e) {
            $this->crmHelper->logCritical($e->getMessage());
            return ['resultCode' => Result::ACCOUNT_LOCKED];
        } catch (\Exception $e) {

            $this->crmHelper->logCritical($e->getMessage());
            if($e instanceof EmailNotConfirmedException){
                return [
                    'resultCode' => Result::INTERNAL_ERROR,
                    'additional' =>  [
                        'resultExplanation' => __('Check your email to complete your registration!')
                    ],
                ];
            }
            return [
                'resultCode' => Result::INTERNAL_ERROR,
            ];
        }
    }
    private function validateParams($params){
        if ((!isset($params['email']) && !isset($params['memberId'])) || (empty($params['email']) && empty($params['memberId']))) {
            return false;
        }
        return true;
    }
}
