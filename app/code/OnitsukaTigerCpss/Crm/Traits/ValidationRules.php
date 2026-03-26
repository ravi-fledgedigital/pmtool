<?php

namespace OnitsukaTigerCpss\Crm\Traits;

use Cpss\Crm\Logger\Logger;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTigerCpss\Crm\Helper\Data as CustomerHelper;
use OnitsukaTigerCpss\Crm\Model\Btoc\Config\Param;

trait ValidationRules
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $customerHelper;
    /**
     * @param ScopeConfigInterface
     * @param TimezoneInterface
     * @param Logger
     *
     */
    private function __construct(
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezoneInterface,
        Logger $logger,
        Encryptor $encryptor,
        CustomerHelper  $customerHelper,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->timezone = $timezoneInterface;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->customerHelper = $customerHelper;
    }
    /**
     * getConfigValue
     *
     * @param  string $path
     * @param  null|int|string $scope
     * @return string
     */
    public function getConfigValue($path, $scope = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $scope);
    }
    /**
     * @param $password
     * @return bool
     */
    function validateWeakPassword($password)
    {
        $rules = array(
            '/\p{Lu}/u', // Uppercase letters
            '/\p{Ll}/u', // Lowercase letters
            '/\p{N}/u', // Numbers
            '/[!@#$%^&*]/' // Special characters
        );
        $satisfyingRules = 0;

        foreach ($rules as $rule) {
            if (preg_match($rule, $password)) {
                $satisfyingRules++;
            }
        }
        if (preg_match('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]+/u', $password)) {
            $satisfyingRules++;
        }
        return $satisfyingRules >= 3;
    }

    /**
     * @param $dob
     * @param int $length
     * @param string $format
     * @return bool
     */
    public function validateDOB($dob, int $length = 18, string $format = 'Ymd'): bool
    {
        // Convert DOB string to a DateTime object
        $date = \DateTime::createFromFormat($format, $dob);
        if (!$date) {
            return false;
        }
        $currentDate = new \DateTime();
        $age = $currentDate->diff($date)->y;
        if ($age < $length) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $string
     * @return bool
     */
    public function isClean($string):bool{
        $pattern1 = '/[!@#$%^&*(),.?":{}|<>]/';
        $pattern2 = '/\d/';
        if (preg_match($pattern1, $string) || preg_match($pattern2, $string) ) {
            return false;
        }
        return true;
    }

    /**
     * @param $string
     * @return bool
     */
    public function  validateRightSpace($string): bool
    {
        // Remove the trailing spaces from the string
        $trimmedString = rtrim($string);
        // Check if the trimmed string is different from the original string
        if ($trimmedString !== $string) {
            return false;
        }
        return true;
    }

    /**
     * @param $field
     * @return bool
     */
    public function isValidSpecialCharacter($field,$storeCode = null): bool
    {
        $enableValidationCharacter = 'customer_address_validate/general/enable_validation_character';
        $addressValidationCharacter = $this->getConfigValue($enableValidationCharacter,$storeCode);
        if (!empty($addressValidationCharacter) && strpos($field, $addressValidationCharacter)) {
            return false;
        }
        return true;
    }

    /**
     * @param $params
     * @return bool
     */
    public function isValidAddressLimitCharacter($params,$storeCode = null): bool
    {
        $pathLimitCharacter = 'customer_address_validate/general/enable_limitations';
        $enableLimitations = $this->getConfigValue($pathLimitCharacter,$storeCode);
        if(!$enableLimitations){
            return true;
        }
        $address1 = $params[Param::ADDRESS_1] ?? '';
        $address2 = $params[Param::ADDRESS_2] ?? '';
        $address = $address1.$address2;
        $pathMaxLimitCharacter = 'customer_address_validate/general/limitations_character';
        $maxLimitCharacter = $this->getConfigValue($pathMaxLimitCharacter,$storeCode);
        if (!empty($maxLimitCharacter) && (int)strlen($address) < (int)$maxLimitCharacter) {
            return true;
        }
        return false;
    }

    /**
     * @param $string
     * @return bool
     */
    public function validateChainingString($string)
    {
        $error = true;
        if (strlen($string) == 0) {
            return $error;
        }
        // Trim the string to remove leading and trailing spaces
        $trimmedString = trim($string);

        // Check if the trimmed string is empty
        if (strlen($trimmedString) === 0) {
            $error = false;
        }
        // String is valid
        return $error;
    }


    /**
     * @param $siteId
     * @param mixed $phoneNumber
     * @return bool|int
     */
    public function validateRequiredLengthPhone($siteId, mixed $phoneNumber)
    {
        switch ($siteId) {
            case 3:
                return preg_match('/^\d{8,10}$/', $phoneNumber); // 8 to 10 digits
            case 1:
            case 2:
                return preg_match('/^\d{8,9}$/', $phoneNumber); // 8 to 9 digits
            case 5:
            case 4:
                return preg_match('/^\d{8,12}$/', $phoneNumber); // 8 to 12 digits
            default:
                return true; // Invalid country code
        }
    }

    /**
     * @param $siteId
     * @param $postalCode
     * @return bool|int
     */
    public function validateRequiredLengthPostalCode($siteId, $postalCode)
    {
        switch ($siteId) {
            case 1:
                return preg_match('/^\d{6}$/', $postalCode); // 6 digits
            case 3:
            case 2:
            case 4:
            case 5:
                return preg_match('/^\d{5}$/', $postalCode); // 5 digits
            default:
                return true; // Invalid country code
        }
    }

    /**
     * @param $data
     * @param $params
     * @return bool
     */
    public function validateEmptyParams($data, $params)
    {
        foreach ($data as $key => $value) {
            $validEmpty = in_array($key, $params) && !empty($value);
            if ($validEmpty) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $string
     * @return false|int
     */
    public function isAlphaNumericSymbols($string)
    {
        return preg_match("/\w+|\W+/", $string);
    }

    /**
     * String does not start with an empty space.
     * @param $string
     * @return bool
     */
    public function isStartWithEmpty($string)
    {
        if (substr($string, 0, 1) === " ") {
            return false;
        }
        return true;
    }

    /**
     * @param $paramKeys
     * @param $params
     * @return mixed
     */
    private function trimParameters($params)
    {
        foreach ($params as $key => $value) {
            if (!in_array($key,[Param::PASSWORD])) {
                $params[$key] = trim($value);
            }
        }
        return array_filter($params, function($value) {
            return strlen($value) !== 0;
        });
    }

    /**
     * @param $data
     * @param $param
     * @return bool
     */
    public function validateConditionalRequired($data, $param)
    {
        switch ($param) {
            case Param::POSTAL_CODE_1:
                if ((isset($data[Param::POSTAL_CODE_1]) && !empty($data[Param::POSTAL_CODE_1]))) {
                    return true;
                }
                break;
            case Param::PHONE_1:
                if ((isset($data[Param::PHONE_1]) && !empty($data[Param::PHONE_1]))) {
                    return true;
                }
                break;
            case Param::PREFECTURE:
                if (isset($data[Param::PREFECTURE]) && !empty($data[Param::PREFECTURE])) {
                    $countryCode = !empty($data[Param::COUNTRY_CODE]) ?? "";
                    $region = $this->customerHelper->getRegionIdByName(
                        $data[Param::PREFECTURE],
                        $countryCode
                    );
//                    if (!$region) {
//                        return true;
//                    }
                }
                break;
        }
        return false;
    }

    /**
     * validate required length
     *
     * @param string $value
     * @param string $key
     * @param int $length
     * @return bool
     */
    public function validateRequiredLength($value, $key, $length)
    {
        $valueLength = strlen($value);
        switch ($key) {
            case Param::PASSWORD:
                $range = [
                    'min' => $this->getMinimumPasswordLength(),
                    'max' => $length
                ];
                return $valueLength >= $range['min'] && $valueLength <= $range['max'] ? false : true;
                break;
        }
    }

    /**
     * Get minimum password length
     *
     * @return string
     * @since 100.1.0
     */
    public function getMinimumPasswordLength($scope = null)
    {
        return $this->getConfigValue(AccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH,$scope);
    }

    /**
     * Validate Characters
     *
     * @param mixed $string
     * @param mixed $type
     * @return bool
     */
    public function validateAllowedChar($string, $type, $key)
    {
        $result = false;
        switch ($type) {
            case Param::NUMERIC:
                $result = !$this->isNumeric($string);
                break;
            case Param::ALPHA_NUMERIC:
                $result = !$this->isAlphaNumeric($string);
                break;
            case Param::ALPHA_NUMERIC_SYMBOLS:
                $result = !$this->isAlphaNumericSymbols($string);
                break;
            case Param::EMAIL_VALIDATION:
                $result = !$this->isEmail($string);
                break;
            case Param::ALPHA:
                $result = !$this->isAlpha($string);
                break;
            case Param::ALPHA_NUMERIC_SPACE:
                $result = !$this->isAlphaNumericSpace($string);
                break;
        }
        return $result;
    }

    /**
     * @param $string
     * @return false|int
     */
    public function isEmail($string)
    {
        return preg_match('/(^[.+\-_a-zA-Z0-9]+@[.\-_a-zA-Z0-9]+$)/', $string);
    }

    /**
     * @param $string
     * @return false|int
     */
    public function isAlpha($string)
    {
        return preg_match("/^[a-zA-Z_]*$/", $string);
    }

    /**
     * @param $string
     * @return false|int
     */
    public function isAlphaNumericSpace($string)
    {
        return preg_match("/^[a-zA-Z0-9_\\s]*$/", $string);
    }
    /**
     * @param $string
     * @return false|int
     */
    public function isAlphaNumericSpaceDash($string)
    {
        return preg_match("/^[a-zA-Z0-9_\-\\s]*$/", $string);
    }

    /**
     * @param $input
     * @return bool
     */
    public function validateNumberWithUnderscore($input) {
        $input = trim($input);
        $pattern = '/^[\W_]+$/';
        return true;
        if (preg_match($pattern, $input)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $input
     * @return bool
     */
    public function validatePurchaseId($purchaseId,$shopId) {
        $purchaseId = trim($purchaseId);
        $pattern = '/^\d{8}_\w{6}_\d{4}_\d{8}$/';
        if (!preg_match($pattern, $purchaseId)) {
            return false;
        }

        $breakDown = explode("_", $purchaseId);
        $count = count($breakDown);
        if ($count <> 4) {
            return false;
        }

        if (!$this->dateFormat($breakDown[0])) {
            return false;
        }

         if (($breakDown[1] <> str_pad($shopId, 6, '0', STR_PAD_LEFT))) {
             return false;
         }

        if (strlen($breakDown[1]) <> 6 || strlen($breakDown[2]) <> 4 || strlen($breakDown[3]) <> 8) {
            return false;
        }

        return true;
    }
}
