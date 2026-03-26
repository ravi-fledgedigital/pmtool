<?php

namespace OnitsukaTigerCpss\Crm\Helper;

use Cpss\Crm\Helper\Customer as CustomerHelper;
use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\Btoc\Config\Result;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\Website;
use OnitsukaTigerCpss\Crm\Model\Btoc\Config\Param;
use OnitsukaTigerCpss\Crm\Traits\ValidationRules;

class MemberValidation extends \Cpss\Crm\Helper\MemberValidation
{
    use ValidationRules;
    const PARAMS_LENGTH_ID = [
        Param::LASTNAME => 15,
        Param::FIRSTNAME => 15,
    ];
    const SUBSCRIBER_STATUS = [
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_NOT_ACTIVE,
        Subscriber::STATUS_UNSUBSCRIBED,
        Subscriber::STATUS_UNCONFIRMED
    ];
    const COUNTRY_SITE_INDO = "5";
    const COUNTRY_SITE_KR = "4";
    const MIN_AGE = 18;
    const MIN_AGE_KR = 14;
    const WEBSITE_COUNTRY_CODE = [
        1 => 'SG',
        2 => 'TH',
        3 => 'MY',
        4 => 'KR',
        5 => 'ID',
        6 => 'VN',
    ];
    // allow for SG site
    // add more site
    const WEBSITE_COUNTRY_ID = [702];
    //parameter that allowed prefix
    const INCLUDE_PREFIX = [
    ];
    protected $timezone;
    protected $logger;
    protected $customerHelper;
    protected Website $website;
    protected $storeCode;
    public function __construct(
        TimezoneInterface    $timezoneInterface,
        Logger               $logger,
        ScopeConfigInterface $scopeConfig,
        Encryptor            $encryptor,
        CustomerHelper       $customerHelper,
        Website              $website
    )
    {
        $this->timezone = $timezoneInterface;
        $this->logger = $logger;
        $this->customerHelper = $customerHelper;
        $this->website = $website;
        parent::__construct($timezoneInterface, $logger, $scopeConfig, $encryptor, $customerHelper);
    }
    /**
     * @param array $data
     * @return bool
     */
    public function validateBillingAddress(array $data): bool
    {
        $params = [
            "postalCode1",
            "prefecture",
            "address1",
            "phone1",
        ];

        return $this->validateEmptyParams($data, $params);
    }


    /**
     * @param $websiteId
     * @param $code
     * @return bool
     */
    public function validateCountryCodeByWebsiteId($websiteId, $code): bool
    {
        $websiteIds = array_keys(self::WEBSITE_COUNTRY_CODE);
        return in_array($websiteId, $websiteIds) && in_array($code, self::WEBSITE_COUNTRY_CODE)
            && strtolower(self::WEBSITE_COUNTRY_CODE[$websiteId]) == strtolower($code);
    }



    /**
     * Not include for character validation
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function notIncludePrefixForCharValidation($key, $value)
    {
        if (isset(self::INCLUDE_PREFIX[$key])) {
            $prefix = self::INCLUDE_PREFIX[$key];
            return preg_replace("/^[$prefix]/", '', $value);
        }

        return $value;
    }

    public function validateExistSite($params){
        $error = Result::INVALID_PARAMS;
        //Check if valid site Id
        if (empty($params[Param::SITE_ID])) {
            return Result::ACCESS_DENIED;
        }
        $website = $this->website->load($params[Param::SITE_ID]);
        if (!$website->getId()) {
            $this->logger->critical(__('Website is not found'));
            return Result::ACCESS_DENIED;
        }
        if (!empty($params[Param::COUNTRY_CODE]) && !$this->validateCountryCodeByWebsiteId($params[Param::SITE_ID], $params[Param::COUNTRY_CODE])) {
            $this->logger->critical(__(' %1 is Required Parameter Equal %2.', Param::SITE_ID, Param::COUNTRY_CODE));
            return $error;
        }
        $incorrectSiteIdLength = $this->validateStringBytes(
            $params[Param::SITE_ID],
            Param::PARAMS_LENGTH[Param::SITE_ID]
        );
        if ($incorrectSiteIdLength || $params[Param::SITE_ID] == "") {
            return $error;
        }
        return Result::SUCCESS;
    }
    /**
     * @param $rules
     * @param $data
     * @return int
     */
    public function validateData($rules, $data)
    {
        $error = Result::SUCCESS;
        //Check if valid site Id
        if (empty($data[Param::SITE_ID])) {
            $this->logger->critical(__(' %1 is Required.', Param::SITE_ID));
            return Result::ACCESS_DENIED;
        }
        $website = $this->website->load($data[Param::SITE_ID]);
        if (empty($website->getId())) {
            $this->logger->critical(__(' %1 is denied.', Param::SITE_ID));
            return Result::ACCESS_DENIED;
        }
        if (!empty($data[Param::COUNTRY_CODE]) && !$this->validateCountryCodeByWebsiteId($data[Param::SITE_ID], $data[Param::COUNTRY_CODE])) {
            $this->logger->critical(__(' %1 is Required Parameter Equal %2.', Param::SITE_ID, Param::COUNTRY_CODE));
            return Result::INVALID_PARAMS;
        }
        $incorrectSiteIdLength = $this->validateStringBytes(
            $data[Param::SITE_ID],
            Param::PARAMS_LENGTH[Param::SITE_ID]
        );
        if ($incorrectSiteIdLength || $data[Param::SITE_ID] == "") {
            $this->logger->critical(__('Website is not found'));
            return Result::INVALID_PARAMS;
        }
        //lastname firstname for ID
        if ( isset($data[Param::SITE_ID]) && $data[Param::SITE_ID] == self::COUNTRY_SITE_INDO) {
            $rulesIdName = [
                Param::LASTNAME => Param::RULE_OPTIONAL.'|maxlength:15',
                Param::FIRSTNAME => Param::RULE_OPTIONAL.'|maxlength:15',
            ];
            $rules = array_merge($rules,$rulesIdName);
        }
        //$errors = [];
        foreach ($rules as $field => $rule) {
            $rulesList = explode('|', $rule);

            if (in_array($rulesList[0] ,[Param::RULE_OPTIONAL,Param::RULE_CONDITIONALLY_REQUIRED])
                && isset($data[$field]) && strlen($data[$field]) == 0) {
                continue; // Skip further validation for optional field
            }
            foreach ($rulesList as $ruleItem) {
                $params = explode(':', $ruleItem);
                $ruleName = $params[0];
                $ruleValue = isset($params[1]) ? $params[1] : null;


                switch ($ruleName) {
                    case  Param::RULE_REQUIRED:
                        if (!isset($data[$field]) || empty($data[$field])) {
                            //$errors[$field] = 'Field is required.';
                            $this->logger->critical(__('"%1" Field is required', $field));
                            $error  = Result::INVALID_PARAMS;
                        }
                        break;

                    case Param::RULE_OPTIONAL:
                        if (!isset($data[$field]) || empty($data[$field])) {
                            continue 2; // Skip further validation for optional field
                        }
                        break;
                    case Param::RULE_CONDITIONALLY_REQUIRED:
                        if ((isset($data[$field]) && !empty($data[$field]))) {
                            continue 2;
                        }
                        break;
                    case 'string':
                        if (isset($data[$field]) && !is_string($data[$field])) {
                            //$errors[$field] = 'Field must be a string.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('"%1" must be a string', $field));
                        }
                        break;

                    case 'numeric':
                        if (isset($data[$field]) && !$this->isNumeric($data[$field])) {
                            //$errors[$field] = 'Field must be numeric.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('"%1" must be a numeric', $field));
                        }
                        break;

                    case 'range':
                        $rangeValue = explode(',',$ruleValue);
                        if (isset($data[$field]) && !is_numeric((int)$data[$field])) {
                            //$errors[$field] = 'Field must be numeric.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('"%1" must be a numeric', $field));
                        } elseif (isset($data[$field]) && ($data[$field] < $rangeValue[0] || $data[$field] > $rangeValue[1])) {
                            //$errors[$field] = 'Field must be between ' . $ruleValue[0] . ' and ' . $ruleValue[1] . '.';
                            $this->logger->critical(__('"%1" must be a between', $field));
                            $error  = Result::INVALID_PARAMS;
                        }
                        break;

                    case 'maxlength':
                        if (isset($data[$field]) && $this->validateStringBytes($data[$field],$ruleValue)) {
                            //$errors[$field] = 'Field length exceeds maximum limit of ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('Value on %1 exceeds the max length(%2 bytes)', $field, $ruleValue));
                        }
                        break;

                    case 'email':
                        if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                            //$errors[$field] = 'Field must be a valid email address.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('"%1" must be a valid email address', $field));
                        }
                        break;
                    case 'password':
                        if (!empty($data[$field]) && !$this->validateWeakPassword($data[$field])) {
                            //$errors[$field] = 'Field must be a strong password.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('"%1" be a strong password.', $field));
                        }
                        if (!empty($data[$field]) &&  (!$this->validateRightSpace($data[$field])
                                || !$this->isStartWithEmpty($data[$field]))) {
                            //$errors[$field] = 'Field should not contain a trailing space.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('"%1" should not contain a trailing space.', $field));
                        }

                        break;
                    case 'alpha':
                        if (isset($data[$field]) && !$this->isAlpha($data[$field])) {
                            //$errors[$field] = 'Field is allowed Character.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 Allowed Character: %2', $field, Param::ALLOWED_CHAR_LABEL[Param::ALPHA]));
                        }
                        break;
                    case 'min':
                        if (isset($data[$field]) && $data[$field] < $ruleValue) {
                            // $errors[$field] = 'Field must be at least ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 must be at least: %2', $field, $ruleValue));
                        }
                        break;

                    case Param::RULE_ALPHA_NUMERIC_SPACE:
                        if (isset($data[$field]) && !$this->isAlphaNumericSpace($data[$field])) {
                            // $errors[$field] = 'Allowed Character ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 Allowed Character: %2', $field, Param::ALLOWED_CHAR_LABEL[Param::ALPHA_NUMERIC_SPACE]));
                        }
                        break;
                    case Param::RULE_ALPHA_NUMERIC_SYMBOLS:
                        if (isset($data[$field]) && !$this->isAlphaNumericSymbols($data[$field])) {
                            // $errors[$field] = 'Allowed Character ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 Allowed Character: %2', $field, Param::ALLOWED_CHAR_LABEL[Param::ALPHA_NUMERIC_SYMBOLS]));
                        }
                        break;
                    case Param::RULE_ALPHA_NUMERIC:
                        if (isset($data[$field]) && !$this->isAlphaNumeric($data[$field])) {
                            // $errors[$field] = 'Allowed Character ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 Allowed Character: %2', $field, Param::ALLOWED_CHAR_LABEL[Param::ALPHA_NUMERIC]));
                        }
                        break;
                    case Param::RULE_CLEAN:
                        if (isset($data[$field]) && !$this->isClean($data[$field])) {
                            // $errors[$field] = 'Field not clean ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 Field not clean', $field));
                        }
                        break;
                    case 'special_characters':
                        if (isset($data[$field]) && !$this->isValidSpecialCharacter($data[$field])) {
                            // $errors[$field] = 'Allowed Character ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 Allowed Character: %2', $field, Param::ALLOWED_CHAR_LABEL[Param::ALPHA_NUMERIC]));
                        }
                        break;
                    case 'max_length_address':
                        if (isset($data[$field]) && !$this->isValidAddressLimitCharacter($data)) {
                            // $errors[$field] = 'Max Length Address Character ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 Max Length Address Character', $field));
                        }
                        break;
                    case 'chaining_spaces':
                        if (isset($data[$field]) && !$this->validateChainingString($data[$field])) {
                            // $errors[$field] = 'String is only space';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 String is only space', $field));
                        }
                        break;
                    case 'phone':
                        if (isset($data[$field]) && !$this->validateRequiredLengthPhone($data[Param::SITE_ID],$data[$field])) {
                            // $errors[$field] = 'Phone is only space';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1  exceeds the max or min', $field));
                        }
                        break;
                    case 'postal_code':
                        if (isset($data[$field]) && !$this->validateRequiredLengthPostalCode($data[Param::SITE_ID],$data[$field])) {
                            // $errors[$field] = '  exceeds the max or min';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1  exceeds the max or min', $field));
                        }
                        break;
                    case 'dob':
                        if (isset($data[$field]) && !$this->dateFormat($data[$field])) {
                            // $errors[$field] = '  Invalid Format';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 %2 Invalid Format: %3', $field, $data[$field], "YYYYMMDD"));
                        }
                        $minAge = self::MIN_AGE;
                        if ($data[Param::SITE_ID] == self::COUNTRY_SITE_KR) {
                            $minAge = self::MIN_AGE_KR;
                        }
                        if (isset($data[$field]) && !$this->validateDOB($data[$field], $minAge)) {
                            $this->logger->critical(__('%1 %2 age >  %3', $field, $data[$field], $minAge));
                            $error  = Result::INVALID_PARAMS;
                        }
                        break;
                    // Add more rule cases as needed
                    default:
                        // Unsupported rule
                        $error  = Result::INVALID_PARAMS;
                        $this->logger->critical(__('"%1" Unsupported rule', $field));
                        break;
                }
                if($error != Result::SUCCESS){
                    return $error;
                }
            }
        }
        return $error;
    }

}
