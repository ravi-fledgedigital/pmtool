<?php

namespace OnitsukaTigerCpss\Crm\Helper;

use Cpss\Crm\Helper\Customer;
use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\Btoc\Config\Param;
use Cpss\Crm\Model\Btoc\Config\Result;
use League\ISO3166\ISO3166 as CountryList;
use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends Customer
{
    const PARAMS_REGISTERED_FROM_APP = "registeredfromapp";
    protected $countryList;

    public function __construct(
        TimezoneInterface $timezoneInterface,
        Logger $logger,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CustomerAccountManagement $customerAccountManagement,
        ScopeConfigInterface $scopeConfig,
        Config $eavConfig,
        Encryptor $encryptor,
        CustomerFactory $customerFactory,
        Http $request,
        RegionCollectionFactory $regionCollectionFactory,
        \Cpss\Crm\Helper\Data $helperData,
        CountryList $countryList,
        EmailValidator $emailValidator = null
    ) {
        $this->countryList = $countryList;
        parent::__construct(
            $timezoneInterface,
            $logger,
            $customerSession,
            $storeManager,
            $customerAccountManagement,
            $scopeConfig,
            $eavConfig,
            $encryptor,
            $customerFactory,
            $request,
            $regionCollectionFactory,
            $helperData,
            $emailValidator
        );
    }
    public function validateRegisterFromApp($request): bool
    {
        $registeredfromapp = explode('registeredfromapp', $request[1]);
        if (!empty($registeredfromapp)) {
            return true;
        }
        return false;
    }
    /**
     * validateAppLoginCredentials
     *
     * @param  mixed $request
     * @return bool
     */
    public function validateAppLoginCredentials($request): bool
    {
        if (!isset($request['app_id']) || !isset($request['client_id'])) {
            return false;
        }

        $appIds = explode(',', $this->getLoginAppId());
        $clientIds = explode(',', $this->getLoginClientId());

        if (in_array($request['app_id'], $appIds) && in_array($request['client_id'], $clientIds)) {
            return true;
        }

        return false;
    }

    /**
     * getAppRedirectUrl
     * redirect to A
     * Redirect after temporary registration
     * @return mixed
     */
    public function getAppRedirectUrl($customerId)
    {
        return $this->getLoginRedirectTo();
    }

    /**
     * redirect to B
     * Redirect after full registration
     * @param $customerId
     * @return string
     */
    public function getAppRedirectUrlRegister($customerId)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Login Redirect After Register: ' . $this->getLoginRedirectAfterRegister());

        return $this->getLoginRedirectAfterRegister() . '?' . $this->buildQuery($customerId);
    }

    public function buildQuery($customerId)
    {
        $loggedInCustomer = $this->getCustomerFactory()->load($customerId);
        $hashedPassword = $loggedInCustomer->getPasswordHash();
        $accessToken = $this->generateAccessToken($hashedPassword);
        $requestApplogin = $this->_customerSession->getAppLoginRequest();

        $prefix = $this->helperData->getCpssMembeIdPrefix();
        $memberId = $prefix . $this->_customerSession->getCustomerId();

        $query = "app_id=" . ($requestApplogin['app_id'] ?? '');
        $query .= "&client_id=" . ($requestApplogin['client_id'] ?? '');
        $query .= "&redirect_to=" . ($requestApplogin['redirect_to'] ?? '');
        $query .= "&access_token=" . $accessToken;
        $query .= "&member_id=" . $memberId;
        $query .= "&country_code=" . $this->helperData->getCountryCode();

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Build Query: ' . $query);

        return $query;
    }

    /**
     * @return string
     */
    public function buildConfirmQuery($clientId = null)
    {
        if (empty($clientId)) {
            $clientIds = explode(',', $this->getLoginClientId());
            $clientId = $clientIds[0];
        }
        $query = "app_id=" . ($this->getLoginAppId()?? '');
        $query .= "&client_id=" . $clientId;
        $query .= "&redirect_to=" . ($this->getLoginRedirectAfterRegister() ?? '');
        $query .= "&country_code=" . ($this->helperData->getCountryCode() ?? '');
        $query .= "&registeredfromapp=true";
        return $query;
    }
    /**
     * @param $websiteId
     * @return int
     * @throws LocalizedException
     */
    public function getStoreDefaultId($websiteId = null)
    {
        return $this->_storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
    }

    /**
     * @param $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore($storeId = null)
    {
        return $this->_storeManager->getStore($storeId);
    }
    /**
     * @return int
     * // Associate website_id with customer
     */
    public function getWebsiteAssociateCustomer($customer)
    {
        if (!$customer->getWebsiteId()) {
            return $this->_storeManager->getStore($customer->getStoreId())->getWebsiteId();
        }
        return $customer->getWebsiteId();
    }

    /**
     * @param $password
     * @param $accessToken
     * @param $websiteId
     * @param $orderHistoryData
     * @return int
     */
    public function authByWebsite($password, $accessToken, $websiteId = null, $orderHistoryData = [])
    {
        if (!$password && !$accessToken && is_array($orderHistoryData)) {
            $customer = $this->getCustomerFactory();
            $customer->setWebsiteId($websiteId);
            $customer = $customer->load($orderHistoryData[Param::MEMBER_ID]);
            return $this->validateToken($customer->getPasswordHash(), $orderHistoryData[Param::ACCESS_TOKEN]);
        } else {
            return $this->validateToken($password, $accessToken);
        }
    }

    public function validateEmailAvailable($email, $websiteId = null)
    {
        if ($websiteId === null) {
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        }
        if (!$this->customerAccountManagement->isEmailAvailable($email, $websiteId)) {
            return Result::EMAIL_EXISTS;
        }
        return Result::SUCCESS;
    }

    /**
     *  Redirect to B
     * @return mixed
     */
    public function getLoginRedirectAfterRegister()
    {
        return $this->_scopeConfig->getValue('crm/login_from_apps/redirect_to_register');
    }

    public function joinParams($params = [])
    {
        $result = [];
        foreach ($params as $param) {
            $list = explode('=', $param);
            if (empty($list[1])) {
                return $result;
            }
            $result[$list[0]] = $list[1];
        }
        return $result;
    }

    public function getCountryCode()
    {
        return $this->helperData->getCountryCode();
    }

    public function getCountryById($id)
    {
        $isoData = $this->countryList->numeric($id);
        return isset($isoData) ? $isoData : null;
    }

    /**
     * isModuleEnabled
     *
     * @return boolean
     */
    public function isModuleEnabled()
    {
        $pointService = $this->_customerSession->getPointServiceEnabled();
        $isLoggedIn = $this->_customerSession->isLoggedIn();
        $isAgreed = $this->_customerSession->getCustomer()->getData('is_agreed') == 1;
        return $this->_scopeConfig->getValue(\Cpss\Crm\Helper\Data::CRM_ENABLED_PATH, ScopeInterface::SCOPE_STORE)
            && $pointService && $isLoggedIn && $isAgreed;
    }

    public function getGender($genderVal, $isMagento = true)
    {
        $attribute = $this->eavConfig->getAttribute('customer', 'gender');
        $optionArray = $attribute->getSource()->toOptionArray();
        $genderKeys = array_keys($optionArray);
        if (in_array($genderVal, $genderKeys)) {
            return $genderVal;
        }
        return "";
    }

    /**
     * @return mixed|null
     */
    public function getTelephoneCountryCode($customer)
    {
        $storeId = $customer->getStoreId();
        $scopeCode = $this->_storeManager->getStore($storeId)->getCode();
        if (!$this->_scopeConfig->getValue('general/telephone_prefix/enable', ScopeInterface::SCOPE_STORE, $scopeCode)) {
            return '';
        }
        return  $this->_scopeConfig->getValue('general/telephone_prefix/number', ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    /**
     * @param $name
     * @param $countryId
     * @return string|null
     */
    public function getRegionIdByName($name, $countryId)
    {
        $regionCollection = $this->regionCollectionFactory->create()
            ->addCountryFilter(strtoupper($countryId))
            ->getItemByColumnValue('default_name', $name);

        if ($regionCollection != null) {
            $regionId = $regionCollection->getRegionId();
            if ($regionId > 0) {
                return $regionId;
            }
        }
        return null;
    }
}
