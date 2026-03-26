<?php

namespace OnitsukaTigerCpss\Crm\Helper;

use Cpss\Crm\Helper\Validation as CrmValidation;
use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\Btoc\Config\Param as CrmParam;
use Cpss\Crm\Model\RealStoreFactory as RealStore;
use Cpss\Crm\Model\Shop\Config\Result;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use OnitsukaTigerCpss\Crm\Model\Shop\Config\Param;
use OnitsukaTigerCpss\Crm\Traits\ValidationRules;

class Validation extends CrmValidation
{
    use ValidationRules;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $realStoreFactory;
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezoneInterface,
        Logger $logger,
        Encryptor $encryptor,
        RealStore $realStoreFactory
    ) {
        $this->realStoreFactory = $realStoreFactory;
        parent::__construct($scopeConfig, $timezoneInterface, $logger, $encryptor, $realStoreFactory);
    }

    /**
     * validateStringBytes
     *
     * @param  string $string
     * @param  int $maxByte
     * @return bool
     */
    public function validateStringBytes($string, $maxByte)
    {
        return (strlen($string) > $maxByte);
    }

    public function getDateTime($format = "Y-m-d H:i:s")
    {
        return date($format);
    }

    /**
     * Check if value is numeric
     *
     * @param  string $value
     * @return bool
     */
    public function isNumeric($value)
    {
        return preg_match('/^[0-9]+$/', $value);
    }

    /**
     * Check if string is alpha numeric
     *
     * @param  string $string
     * @return bool
     */
    public function isAlphaNumeric($string)
    {
        return preg_match("/^[a-zA-Z0-9_]*$/", $string);
    }

    /**
     * Check if value is only combination of aplha numeric and symbols
     *
     * @param  string $string
     * @return bool
     */
    public function isAlphaNumericSymbols($string)
    {
        return true;
    }

    /**
     * @param $paramKeys
     * @param $data
     * @return int|void
     */
    public function validateParams($paramKeys, $data)
    {
        if (!$this->enabled()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                "message" => __('API endpoints are currently disabled. Please try again later.')
            ]);
            exit;
        }

        $error = Result::SUCCESS;
        $success = Result::SUCCESS;
        //$errors = [];
        foreach ($paramKeys as $field => $rule) {
            $rulesList = explode('|', $rule);

            if (in_array($rulesList[0], [Param::RULE_OPTIONAL,Param::RULE_CONDITIONALLY_REQUIRED])
                && !isset($data[$field])
                || ($this->isOptional($data, $field, $rulesList[0]))) {
                $this->logger->critical(__('"%1" Skip further validation for optional field.', $field));
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
                            $this->logger->critical(__('"%1" is Required Parameter.', $field));
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
                        $rangeValue = explode(',', $ruleValue);
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
                        if (isset($data[$field]) && $this->validateStringBytes($data[$field], $ruleValue)) {
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
                            $this->logger->critical(__('%1 Allowed Character: %2', $field, Param::ALLOWED_CHAR_LABEL[CrmParam::ALPHA]));
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
                            $this->logger->critical(__('%1 Allowed Character: %2', $field, Param::ALLOWED_CHAR_LABEL[CrmParam::ALPHA_NUMERIC_SPACE]));
                        }
                        break;
                    case Param::RULE_ALPHA_NUMERIC_SYMBOLS:
                        if (isset($data[$field]) && !$this->isAlphaNumericSymbols($data[$field])) {
                            // $errors[$field] = 'Allowed Character ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 Allowed Character: %2', $field, Param::ALLOWED_CHAR_LABEL[CrmParam::ALPHA_NUMERIC_SYMBOLS]));
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
                    case 'chaining_spaces':
                        if (isset($data[$field]) && !$this->validateChainingString($data[$field])) {
                            // $errors[$field] = 'String is only space';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 String is only space', $field));
                        }
                        break;
                    case 'phone':
                        if (isset($data[$field]) && !$this->validateRequiredLengthPhone($data[Param::SITE_ID], $data[$field])) {
                            // $errors[$field] = 'Phone is only space';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1  exceeds the max or min', $field));
                        }
                        break;
                    case 'postal_code':
                        if (isset($data[$field]) && !$this->validateRequiredLengthPostalCode($data[Param::SITE_ID], $data[$field])) {
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
                        $minAge = MemberValidation::MIN_AGE;
                        if ($data[Param::SITE_ID] == MemberValidation::COUNTRY_SITE_KR) {
                            $minAge = MemberValidation::MIN_AGE_KR;
                        }
                        if (isset($data[$field]) && !$this->validateDOB($data[$field], $minAge)) {
                            $this->logger->critical(__('%1 %2 age >  %3', $field, $data[$field], $minAge));
                            $error  = Result::INVALID_PARAMS;
                        }
                        break;
                    case Param::RULE_DEPEND_ON:
                        if (isset($data[$field]) && !$this->dependOn($field, $ruleValue, $data)) {
                            // $errors[$field] = 'Field depend on  ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1  depend on ', $field));
                        }
                        break;
                    case Param::RULE_DEPEND_ON_MIN:
                        if (isset($data[$field]) && !$this->dependOnMin($field, $ruleValue, $data)) {
                            // $errors[$field] = 'Field depend on  ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1  depend on with min length ', $field));
                        }
                        break;
                    case Param::RULE_DEPEND_WITH:
                        if (isset($data[$field]) && !$this->dependWith($field, $ruleValue, $data)) {
                            // $errors[$field] = 'Field depend with  ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1  depend on ', $field));
                        }
                        break;
                    case Param::RULE_NUMBER_UNDERSCORE:
                        if (isset($data[$field]) &&  !$this->validateNumberWithUnderscore($data[$field])) {
                            // $errors[$field] = 'Field number and underscore ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 number and underscore', $field));
                        }
                        break;
                    case Param::RULE_PURCHASE_ID:
                        if (isset($data[$field])
                            && isset($data[Param::SHOP_ID])
                            &&  !$this->validatePurchaseId($data[$field], $data[Param::SHOP_ID])) {
                            // $errors[$field] = 'Field number and underscore ' . $ruleValue . '.';
                            $error  = Result::INVALID_PARAMS;
                            $this->logger->critical(__('%1 validate format purchase id', $field));
                        }
                        break;
                    // Add more rule cases as needed
                    default:
                        // Unsupported rule
                        $error  = Result::INVALID_PARAMS;
                        $this->logger->critical(__('"%1" Unsupported rule', $field));
                        break;
                }
                if ($error != Result::SUCCESS) {
                    return $error;
                }
            }
        }
        return $error;
    }

    /**
     * Validate Characters
     *
     * @param  mixed $string
     * @param  mixed $type
     * @return void
     */
    public function validateAllowedChar($string, $type)
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
        }
        return $result;
    }

    /**
     * Validate if transaction type is the one that was specified
     *
     * @param  int $string
     * @return bool
     */
    public function validateTransactionType($string)
    {
        return empty(Param::TRANSACTION_TYPE_VALUES[$string]);
    }

    /**
     * Check if valid authentication
     *
     * @param  array $string
     * @return int
     */
    public function checkAuth($params)
    {
        return ($params[Param::SHOP_ID] === $this->getShopId() || $this->validateToken($params[Param::ACCESS_TOKEN])) ? Result::SUCCESS : Result::AUTH_FAILED;
    }

    /**
     * Validate Date Format
     *
     * @param  string $date
     * @param  string $format
     * @return void
     */
    public function dateFormat($date, $format = 'Ymd')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return ($d && $d->format($format) === $date) ? true : false;
    }

    /**
     * ValidateToken
     *
     * @param  string $accessToken
     * @return bool
     */
    public function validateToken($accessToken)
    {
        try {
            $defaultToken = $this->encryptor->getHash($this->getShopPass(), $this->getSalt());
            $defaultToken = explode(Encryptor::DELIMITER, $defaultToken);
            $defaultToken = $defaultToken[0];

            return ($accessToken == $defaultToken);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * validateAccessToken
     *
     * @param  mixed $shopId
     * @param  mixed $password
     * @return bool
     */
    public function validateAccessToken($shopId, $accessToken)
    {
        return $this->realStoreFactory->create()->validateAccessToken($shopId, $accessToken);
    }

    public function validatePurchaseIdFormat($purchaseId, $shopId)
    {
        $breakDown = explode("_", $purchaseId);
        $count = count($breakDown);
        if ($count != 4) {
            return __('%1 Invalid purchaseId format: 購入日付(8桁)_店舗ID(5桁)_POSレジ端末No.(4桁)_レシート番号(8桁) ', $purchaseId);
        }

        if (!$this->dateFormat($breakDown[0])) {
            return __('%1 Invalid Date format: YYYYMMDD', $breakDown[0]);
        }

        // if (($breakDown[1] <> str_pad($shopId, 5, '0', STR_PAD_LEFT))) {
        //     return __('%1 does not match shopId value %2', $breakDown[1], $shopId);
        // }

        if (strlen($breakDown[1]) != 5 || strlen($breakDown[2]) != 4 || strlen($breakDown[3]) != 8) {
            return __('%1 Invalid purchaseId section length: 購入日付(8桁)_店舗ID(5桁)_POSレジ端末No.(4桁)_レシート番号(8桁) ', $purchaseId);
        }

        return true;
    }

    public function validateConditionalRequired($data, $param)
    {
        switch ($param) {
            case Param::USED_POINT:
            case Param::POINT_HISTORY_ID:
                if ((isset($data[Param::USED_POINT]) && !empty($data[Param::USED_POINT])) || (isset($data[Param::POINT_HISTORY_ID]) && !empty($data[Param::POINT_HISTORY_ID]))) {
                    return true;
                }
                break;
            case Param::MEMBER_ID:
            case Param::COUNTRY_CODE:
                if (isset($data[Param::TRANSACTION_TYPE]) && $data[Param::TRANSACTION_TYPE] == 1) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * @param mixed $field
     * @param $dependField
     * @param $params
     * @return bool
     */
    private function dependOn(mixed $field, $dependField, $params)
    {
        if (isset($field) && isset($params[$dependField])) {
            return true;
        }
        return false;
    }

    /**
     * @param mixed $field
     * @param $dependField
     * @param $params
     * @return bool
     */
    private function dependOnMin(mixed $field, $dependField, $params)
    {
        if (isset($field) && isset($params[$dependField]) && strlen($field) > 0 && strlen($params[$dependField]) > 0) {
            return true;
        }
        return false;
    }
    /**
     * @param mixed $field
     * @param $dependField
     * @param $params
     * @return bool
     */
    private function dependWith(mixed $field, $dependField, $params)
    {
        if ($params[$field] == Param::TRANSACTION_TYPE_2) {
            return true;
        }
        if (isset($field) && isset($params[$dependField]) && $params[$field] == Param::TRANSACTION_TYPE_1 && !empty($params[$dependField])) {
            return true;
        }
        return false;
    }

    /**
     * @param array $data
     * @param int|string $field
     * @param $rulesList
     * @return bool
     */
    public function isOptional(array $data, int|string $field, $rulesList): bool
    {
        return (isset($data[$field]) && strlen($data[$field]) == 0 && $rulesList != Param::RULE_REQUIRED);
    }
    public function isOptionalWithDepend(array $data, int|string $field, $rulesList): bool
    {
        $dependOn = false;
        foreach ($rulesList as $rule) {
            if (stripos($rule, Param::RULE_DEPEND_ON) !== false) {
                $dependOn = true;
            }
        }
        return $dependOn;
    }
}
