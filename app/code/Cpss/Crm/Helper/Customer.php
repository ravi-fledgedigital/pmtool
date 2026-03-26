<?php

namespace Cpss\Crm\Helper;

use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\Btoc\Config\Param;
use Cpss\Crm\Model\Btoc\Config\Result;
use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Customer extends AbstractHelper
{
    protected $_storeManager;
    protected $_customerSession;
    protected $customerAccountManagement;
    protected $emailValidator;
    protected $_scopeConfig;
    protected $eavConfig;
    protected $parameterError = [];
    protected $encryptor;
    protected $customerFactory;
    protected $request;
    protected $regionCollectionFactory;
    protected $helperData;

    const ZIPCODE_1_MAX = 3;
    const ZIPCODE_2_MAX = 4;
    const TELEPHONE_MAX = 6;
    const TELEPHONE_TOTAL_MAX = 12;
    const NAME_MAX = 50;
    const GENDER_MAPPER = [
        0 => "male",
        1 => "female",
        2 => "not specified"
    ];

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
        Data $helperData,
        EmailValidator $emailValidator = null
    ) {
        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->_scopeConfig = $scopeConfig;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->emailValidator = $emailValidator ?: ObjectManager::getInstance()->get(EmailValidator::class);
        $this->eavConfig = $eavConfig;
        $this->encryptor = $encryptor;
        $this->customerFactory  = $customerFactory;
        $this->request = $request;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->helperData = $helperData;
        parent::__construct($scopeConfig, $timezoneInterface, $logger, $encryptor);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function validateParams($request)
    {
        // Decode data before validating
        $request = $this->decodeRequestData($request);

        // Validate Required Fields
        $this->validateRequiredFields($request);

        // Validate Email
        $email = $request['email'];
        $this->validateEmailFormat($email);

        // Validate Password
        $password = $request['password'];
        $this->validatePasswordField($password, $email);

        // Validate Name
        $lastname = $request['lastName'];
        $firstname = $request['firstName'];
        $lnamelabel = __('Last Name');
        $fnamelabel = __('First Name');
        if ($this->validateWhiteSpace($lastname)) {
            $this->parameterError[] = 'lastName';
        }
        if ($this->validateWhiteSpace($firstname)) {
            $this->parameterError[] = 'firstName';
        }
        if ($this->checkStringMaxLength($lastname, self::NAME_MAX)) {
            $this->parameterError[] = 'lastName';
        }
        if ($this->checkStringMaxLength($firstname, self::NAME_MAX)) {
            $this->parameterError[] = 'firstName';
        }

        // Validate Kana
        if ($this->isKatakana($request['lastNameKana'])) {
            $this->parameterError[] = 'lastNameKana';
        }
        if ($this->isKatakana($request['firstNameKana'])) {
            $this->parameterError[] = 'firstNameKana';
        }

        // Validate Zipcode
        $postcode1 = $request['postalCode1'];
        $postcode2 = $request['postalCode2'];
        if ($this->validateDigits($postcode1) || $this->validateDigits($postcode2)) {
            $this->parameterError[] = 'Post Code';
        }
        if ($this->checkStringRequiredLength($postcode1, self::ZIPCODE_1_MAX)) {
            $this->parameterError[] = 'postalCode1';
        }
        if ($this->checkStringRequiredLength($postcode2, self::ZIPCODE_2_MAX)) {
            $this->parameterError[] = 'postalCode2';
        }

        // Validate Telephone
        $telephone1 = $request['phone1'];
        $telephone2 = $request['phone2'];
        $telephone3 = $request['phone3'];
        $telephone = $telephone1 . $telephone2 . $telephone3;
        if ($this->validateDigits($telephone1) || $this->validateDigits($telephone2) || $this->validateDigits($telephone3)) {
            $this->parameterError[] = 'Telephone';
        }
        if ($this->checkStringMaxLength($telephone1, self::TELEPHONE_MAX) || $this->checkStringMaxLength($telephone2, self::TELEPHONE_MAX) || $this->checkStringMaxLength($telephone3, self::TELEPHONE_MAX)) {
            $this->parameterError[] = 'Telephone';
        }

        if ($this->checkStringMaxLength($telephone, self::TELEPHONE_TOTAL_MAX)) {
            $this->parameterError[] = 'Telephone';
        }
        if (!empty($this->parameterError)) {
            $fields = implode(",", array_unique($this->parameterError));
            return json_encode([
                'resultCode' => 2,
                'resultExplanation' => Result::RESULT_CODES[2] . ": " . $fields
            ]);
        }

        return true;
    }


    /**
     * Validate Whitespace
     *
     * @param string
     * @return Boolean
     */
    public function validateWhiteSpace($value)
    {
        return preg_match('/\s/', $value);
    }

    /**
     * Validate Date Completely
     *
     * @param string
     * @throws LocalizedException
     * @return void
     */
    public function validateCompleteDate($year, $month, $day)
    {
        $counter = 0;
        if (strlen($year) > 0) {
            $counter++;
        }

        if (strlen($month) > 0) {
            $counter++;
        }

        if (strlen($day) > 0) {
            $counter++;
        }

        if (!($counter == 3 || $counter == 0)) {
            throw new LocalizedException(__('Please fill in all fields for the date of birth.'));
        }

        if ($counter == 3) {
            $date = $year . '-' . sprintf("%02d", $month) . '-' . sprintf("%02d", $day);
            if (!($this->validateDate($date))) {
                throw new LocalizedException(__('The date of birth is incorrect.'));
            }
        }
    }

    /**
     * Validate Date
     *
     * @param string
     * @return Boolean
     */
    public function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }

    /**
     * Get minimum password length
     *
     * @return string
     * @since 100.1.0
     */
    public function getMinimumPasswordLength()
    {
        return $this->_scopeConfig->getValue(AccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH);
    }

    /**
     * Get number of password required character classes
     *
     * @return string
     * @since 100.1.0
     */
    public function getRequiredCharacterClassesNumber()
    {
        return $this->_scopeConfig->getValue(AccountManagement::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER);
    }

    /**
     * Validate Password
     *
     * @param string
     * @throws LocalizedException
     * @return void
     */
    public function validatePasswordField($password, $email = '')
    {
        $passwordMinLength = $this->getMinimumPasswordLength();
        $passwordMinCharacterSets = $this->getRequiredCharacterClassesNumber();
        $counter = 0;

        if (!(preg_match('/^[a-z0-9]+$/i', $password))) {
            $this->parameterError[] = 'password';
            // throw new LocalizedException(__('Please enter your password in alphanumeric characters.'));
        }

        if (!(strlen($password) >= $passwordMinLength)) {
            $this->parameterError[] = 'password';
            // throw new LocalizedException(__('Please enter a password between %1 and 50 characters.', $passwordMinLength));
        }

        if (strlen($password) > 50) {
            $this->parameterError[] = 'password';
            // throw new LocalizedException(__('The password should be less than 50 characters.'));
        }

        $emailIndex0 = explode("@", $email);
        if ($password == $emailIndex0[0]) {
            $this->parameterError[] = 'password';
            // throw new LocalizedException(__('The same password as your e-mail address cannot be entered.'));
        }

        if (preg_match('/(.)\1\1/', $password)) {
            $this->parameterError[] = 'password';
            // throw new LocalizedException(__('The password cannot contain more than three consecutive characters.'));;
        }

        // if(!(preg_match('/^[0-9]+$/', $password))){
        //     throw new LocalizedException(__('The password must include all letters and numbers.'));
        // }

        // if(!(preg_match('/^[a-z]+$/i', $password))){
        //     throw new LocalizedException(__('The password should contains half-width numbers.'));
        // }

        if ((preg_match('/\d+/', $password))) {
            $counter++;
        }

        if ((preg_match('/[a-z]+/', $password))) {
            $counter++;
        }

        if ((preg_match('/[A-Z]+/', $password))) {
            $counter++;
        }

        if ((preg_match('/[^a-zA-Z0-9]+/', $password))) {
            $counter++;
        }

        if ($counter < $passwordMinCharacterSets) {
            $this->parameterError[] = 'password';
        }
    }

    /**
     * Checked digit
     *
     * @param string
     * @return Bollean
     */
    public function validateDigits($str)
    {
        return !is_numeric($str);
    }

    /**
     * Checked String Max Length
     *
     * @param string
     * @return Bollean
     */
    public function checkStringMaxLength($str, $max)
    {
        return strlen($str) > $max;
    }

    /**
     * Checked String Required Length
     *
     * @param string
     * @return Bollean
     */
    public function checkStringRequiredLength($str, $req)
    {
        return strlen($str) != $req;
    }

    /**
     * Checked katakana
     *
     * @param string name
     * @return void
     * @throws LocalizedException
     */
    public function isKatakana($str)
    {
        return !(preg_match('/^[\x{30A0}-\x{30FF}]+$/u', $str) > 0);
    }

    /**
     * Make sure that original and confirmation matched
     *
     * @param string $original
     * @param string $confirmation
     * @return Bollean
     */
    public function checkConfirmation($original, $confirmation)
    {
        return $original != $confirmation;
    }

    /**
     * Validates the format of the email address
     *
     * @param string $email
     * @throws LocalizedException
     * @return void
     */
    public function validateEmailFormat($email)
    {
        if (!preg_match('/(^[.+\-_a-zA-Z0-9]+@[.\-_a-zA-Z0-9]+$)/', $email)) {
            $this->parameterError[] = 'email';
            // throw new LocalizedException(__('Please enter a valid email address.'));
        }
    }


    public function validateEmailAvailable($email)
    {
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        if (!$this->customerAccountManagement->isEmailAvailable($email, $websiteId)) {
            return Result::EMAIL_EXISTS;
        }
        return Result::SUCCESS;
    }

    /**
     * Make sure that password and password confirmation matched
     *
     * @param string $password
     * @param string $confirmation
     * @return void
     * @throws InputException
     */
    public function checkPasswordConfirmation($password, $confirmation)
    {
        if ($password != $confirmation) {
            throw new LocalizedException(__('Please make sure your passwords match.'));
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function validateRequiredFields($request)
    {
        $requiredFields = [
            'siteId',
            'lastName',
            'firstName',
            'lastNameKana',
            'firstNameKana',
            'email',
            'countryCode',
            'postalCode1',
            'postalCode2',
            'phone1',
            'phone2',
            'phone3',
            'password'
        ];
        foreach ($request as $key => $value) {
            if (in_array($request[$key], $requiredFields) && empty($request[$key])) {
                $this->parameterError[] = $key;
            }
        }
    }

    public function decodeRequestData($request)
    {
        foreach ($request as $key => $value) {
            if (!is_array($value)) {
                $request[$key] = rawurldecode($value);
            } else {
                foreach ($value as $k => $v) {
                    $request[$key][$k] = rawurldecode($v);
                }
            }
        }
        return $request;
    }

    public function decodeSingleData($value)
    {
        if (!is_array($value)) {
            $value = rawurldecode($value);
        } else {
            foreach ($value as $k => $v) {
                $value[$k] = rawurldecode($v);
            }
        }
        return $value;
    }

    public function getGender($genderVal, $isMagento = true)
    {
        $attribute = $this->eavConfig->getAttribute('customer', 'gender');
        $optionArray = $attribute->getSource()->toOptionArray();
        $result = array_column($optionArray, 'value', 'label');
        $result = array_filter(array_change_key_case($result));

        if ($isMagento) {
            $mappedGender = (isset(self::GENDER_MAPPER[$genderVal])) ? self::GENDER_MAPPER[$genderVal] : false;

            return isset($result[$mappedGender]) ? $result[$mappedGender] : '';
        } else {
            $result = array_flip($result);
            $gender = $result[$genderVal];
            $genderMapp = array_flip(self::GENDER_MAPPER);

            return isset($genderMapp[$gender ]) ? $genderMapp[$gender ] : '';
        }
    }

    /**
     * setMemberId
     *
     * @param  string $memberId
     * @return string
     */
    public function setMemberId($memberId)
    {
        $memberId = str_replace(':USR', '', $memberId);

        $this->_customerSession->setMemberId($memberId);
    }

    /**
     * getMemberId
     *
     * @return string
     */
    public function getMemberId()
    {
        return $this->_customerSession->getMemberId();
    }

    /**
     * isModuleEnabled
     *
     * @return void
     */
    public function isModuleEnabled()
    {
        /*$pointService = $this->_customerSession->getPointServiceEnabled();*/
        $isLoggedIn = $this->_customerSession->isLoggedIn();
        /*return $this->_scopeConfig->getValue(Data::CRM_ENABLED_PATH,ScopeInterface::SCOPE_STORE) && $pointService && $isLoggedIn;*/
        return $this->_scopeConfig->getValue(Data::CRM_ENABLED_PATH,ScopeInterface::SCOPE_STORE) && $isLoggedIn;
    }

    public function generateAccessToken($password)
    {
        $accessToken = $this->encryptor->getHash($password, false, Encryptor::HASH_VERSION_SHA256);
        $accessToken = explode(Encryptor::DELIMITER, $accessToken);
        $accessToken = $accessToken[0];
        return $accessToken;
    }

    public function validateToken($password, $accessToken)
    {
        try {
            $token = $this->generateAccessToken($password);
            if ($accessToken === $token) {
                return Result::SUCCESS;
            }
            return Result::ACCESS_DENIED;
        } catch (\Exception $e) {
            $this->logCritical($e->getMessage());
            return Result::INTERNAL_ERROR;
        }
    }

    /**
     * Customer Authentication
     * @param array $requestData
     * @return mixed
     */
    public function auth($password, $accessToken, $orderHistoryData = [])
    {
        if (!$password && !$accessToken && is_array($orderHistoryData)) {
            $customer = $this->getCustomerFactory()->load($orderHistoryData[Param::MEMBER_ID]);
            return $this->validateToken($customer->getPasswordHash(), $orderHistoryData[Param::ACCESS_TOKEN]);
        } else {
            return $this->validateToken($password, $accessToken);
        }
    }

    public function logCritical($message)
    {
        return $this->logger->critical($message);
    }

    public function logDebug($message, $data)
    {
        return $this->logger->critical($message, $data);
    }

    public function getCustomerFactory()
    {
        return $this->customerFactory->create();
    }

    public function getWebsiteId()
    {
        return $this->_storeManager->getWebsite()->getWebsiteId();
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getParamsRequest()
    {
        return $this->request->getParams();
    }

    /**
     * getRegionIdByName
     *
     * @param  string $name
     * @param  string $countryId
     * @return string
     */
    public function getRegionIdByName($name, $countryId)
    {
        $regionCollection = $this->regionCollectionFactory->create()
            ->addCountryFilter(strtoupper($countryId))
            ->getItemByColumnValue('name', $name);

        if ($regionCollection != null) {
            $regionId = $regionCollection->getRegionId();
            if ($regionId > 0) {
                return $regionId;
            } else {
                return false;
            }
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
     * getLoginAppId
     *
     * @return mixed
     */
    public function getLoginAppId()
    {
        return $this->_scopeConfig->getValue('crm/login_from_apps/app_id');
    }

    /**
     * getLoginClientId
     *
     * @return mixed
     */
    public function getLoginClientId()
    {
        return $this->_scopeConfig->getValue('crm/login_from_apps/client_id');
    }

    public function getRedirectWaitingTime()
    {
        return $this->_scopeConfig->getValue('crm/login_from_apps/redirect_waiting_time');
    }

    /**
     * getLoginRedirectTo
     * Redirect to A
     * @return mixed
     */
    public function getLoginRedirectTo()
    {
        return $this->_scopeConfig->getValue('crm/login_from_apps/redirect_to');
    }

    /**
     * getAppRedirectUrl
     *
     * @return mixed
     */
    public function getAppRedirectUrl($customerId)
    {
        $loggedInCustomer = $this->getCustomerFactory()->load($customerId);
        $hashedPassword = $loggedInCustomer->getPasswordHash();
        $accessToken = $this->generateAccessToken($hashedPassword);
        $requestApplogin = $this->_customerSession->getAppLoginRequest();

        $prefix = $this->helperData->getCpssMembeIdPrefix();
        $memberId = $prefix . $this->_customerSession->getCustomerId();

        $query = "?app_id=" . ($requestApplogin['app_id'] ?? '');
        $query .= "&client_id=" . ($requestApplogin['client_id'] ?? '');
        $query .= "&redirect_to=" . ($requestApplogin['redirect_to'] ?? '');
        $query .= "&access_token=" . $accessToken;
        $query .= "&member_id=" . $memberId;
        $query .= "&country_code=" . $this->helperData->getCountryCode();

        $this->_customerSession->setIsRedirectAppLogin(null);
        $this->_customerSession->setAppLoginRequest(null);

        return $this->getLoginRedirectTo() . $query;
    }
}
