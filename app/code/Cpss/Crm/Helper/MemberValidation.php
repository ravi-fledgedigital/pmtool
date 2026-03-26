<?php

namespace Cpss\Crm\Helper;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Cpss\Crm\Model\Btoc\Config\Result;
use Cpss\Crm\Model\Btoc\Config\Param;
use Cpss\Crm\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Cpss\Crm\Helper\Customer as CustomerHelper;
use Magento\Customer\Model\AccountManagement;

class MemberValidation extends AbstractHelper
{
    protected $timezone;
    protected $logger;
    protected $customerHelper;

    public function __construct(
        TimezoneInterface $timezoneInterface,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Encryptor $encryptor,
        CustomerHelper $customerHelper
    ) {
        $this->timezone = $timezoneInterface;
        $this->logger = $logger;
        $this->customerHelper = $customerHelper;
        parent::__construct($scopeConfig, $timezoneInterface, $logger, $encryptor);
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
        $date = new \DateTime($this->timezone->date()->format($format), new \DateTimeZone("Asia/Tokyo"));
        $date->setTimezone(new \DateTimeZone("UTC"));
        return $date->format($format);
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

    public function isAlphaNumericSymbols($string)
    {
        if ($this->isFullWidth($string)) {
            return false;
        }

        return preg_match("/\w+|\W+/", $string);
    }

    public function isEmail($string)
    {
        return preg_match('/(^[.+\-_a-zA-Z0-9]+@[.\-_a-zA-Z0-9]+$)/', $string);
    }

    public function isFullWidthKanaCharacters($string)
    {
        return (preg_match('/^[\x{30A0}-\x{30FF}]+$/u', $string) > 0);
    }

    public function isAlpha($string)
    {
        return preg_match("/^[a-zA-Z_]*$/", $string);
    }

    public function isAlphaNumericSpace($string)
    {
        return preg_match("/^[a-zA-Z0-9_\\s]*$/", $string);
    }

    public function isAlphaNumericSpaceDash($string)
    {
        return preg_match("/^[a-zA-Z0-9_\-\\s]*$/", $string);
    }

    public function isKana($string)
    {
        return (preg_match('/[\x{30A0}-\x{30FF}]/u', $string) > 0);
    }

    public function mbstrSplit($inStr)
    {
        $splitArr = [];
        $strLen = mb_strlen($inStr);
        $index = 0;
        $splitArr["fullWidth"] = "";
        $splitArr["halfWidth"] = "";
        while ($index < $strLen) {
            $char = mb_substr($inStr, $index, 1);
            if (mb_strwidth($char) == 1) {
                $splitArr["halfWidth"] .= $char;
            } else {
                $splitArr["fullWidth"] .= $char;
            }
            $index++;
        }

        return $splitArr;
    }

    /**
     * Validate Parameters
     *
     * @param  array $paramKeys
     * @param  array $params
     * @return int
     */
    public function validateParams($paramKeys, $params)
    {
        $error = Result::INVALID_PARAMS;
        $success = Result::SUCCESS;

        //Check if valid site Id
        /*if (!(isset($params[Param::SITE_ID]) && $params[Param::SITE_ID] == $this->getSiteId())) {*/
        if (!isset($params[Param::SITE_ID])) {
            if (isset($params[Param::SITE_ID])) {
                $incorrectSiteIdLength = $this->validateStringBytes($params[Param::SITE_ID], Param::PARAMS_LENGTH[Param::SITE_ID]);
                if ($incorrectSiteIdLength || $params[Param::SITE_ID] == "") {
                    return $error;
                }
            }
            return Result::ACCESS_DENIED;
        }

        foreach ($paramKeys as $key => $isRequired) {
            $value = '';
            // $requirement = Param::PARAMS_REQUIREMENT[$key];

            // check required parameter(s)
            if (empty($params[$key]) && $isRequired == Param::REQUIRED) {
                $this->logger->critical(__('%1 is Required Parameter.', $key));
                return $error;
            } elseif (empty($params[$key]) && $isRequired == Param::CONDITIONALLY_REQUIRED) {
                if ($this->validateConditionalRequired($params, $key)) {
                    $this->logger->critical(__('%1 is Required Parameter.', $key));
                    return $error;
                }
                continue;
            } elseif (empty($params[$key]) && ($isRequired == Param::CONDITIONALLY_REQUIRED || $isRequired == Param::OPTIONAL)) {
                continue;
            }

            $value = $params[$key];

            // check over sized parameter(s)
            if (isset(Param::PARAMS_LENGTH[$key])) {
                $length = Param::PARAMS_LENGTH[$key];
                $result = $this->validateStringBytes($value, $length);
                if ($result) {
                    $this->logger->critical(__('Value on %1 exceeds the max length(%2 bytes)', $key, $length));
                    return $error;
                }
            }

            $value = $this->notIncludePrefixForCharValidation($key, $value);

            // check required length
            $length = $key == Param::PHONE_1 ? Param::PARAMS_RANGE[$key] : Param::PARAMS_LENGTH[$key];
            $result = $this->validateRequiredLength($value, $key, $length);
            if ($result) {
                if ($key == Param::PHONE_1) {
                    $length = $length['min'] . ' ~ ' . $length['max'];
                }
                if ($key == Param::PASSWORD) {
                    $length = $this->getMinimumPasswordLength() . ' ~ ' . $length;
                    $error = Result::INVALID_PASSWORD_LENGTH;
                }
                $this->logger->critical(__('Value on %1 does not match the required length(%2 bytes)', $key, $length));
                return $error;
            }

            // check allowed characters
            $num = Param::PARAMS_ALLOWED_CHARACTERS[$key];
            $result = $this->validateAllowedChar($value, $num, $key);
            if ($result) {
                $this->logger->critical(__('%1 Allowed Character: %2', $key, Param::ALLOWED_CHAR_LABEL[$num]));
                return $error;
            }

            if ($key == Param::COUNTRY_CODE && ($value == "" || $value != "JP")) {
                $this->logger->critical(__('%1 should be "JP".', $key));
                return $error;
            }

            if ($key == Param::DOB) {
                $result = $this->dateFormat($value);
                if (!$result) {
                    $this->logger->critical(__('%1 %2 Invalid Format: %3', $key, $value, "YYYYMMDD"));
                    return $error;
                }
            }

            if ($key == Param::PREFECTURE && !empty($value)) {
                if ($this->validateConditionalRequired($params, $key)) {
                    $this->logger->critical(__('%1 is Required Parameter or value is invalid.', $key));
                    return $error;
                }
            }

            //skip validation for phone1
            if (isset(Param::PARAMS_RANGE[$key]) && $key != Param::PHONE_1) {
                $range = Param::PARAMS_RANGE[$key];
                $result = $this->validateRange($value, $range);
                if ($result) {
                    $this->logger->critical(__('"%1" is an invalid %2 value', $value, $key));
                    return $error;
                }
            }
        }

        return $success;
    }

    /**
     * Validate Parameters
     *
     * @param  array $paramKeys
     * @param  array $params
     * @return int
     */
    public function validateParamsForToBApp($paramKeys, $params)
    {
        $error = Result::INVALID_PARAMS;
        $success = Result::SUCCESS;

        //Check if valid site Id
        /*if (!(isset($params[Param::SITE_ID]) && $params[Param::SITE_ID] == $this->getSiteId())) {
            if (isset($params[Param::SITE_ID])) {
                $incorrectSiteIdLength = $this->validateStringBytes($params[Param::SITE_ID], Param::PARAMS_LENGTH[Param::SITE_ID]);
                if ($incorrectSiteIdLength || $params[Param::SITE_ID] == "") {
                    return $error;
                }
            }
            return Result::ACCESS_DENIED;
        }*/

        foreach ($paramKeys as $key => $isRequired) {
            $value = '';
            // $requirement = Param::PARAMS_REQUIREMENT[$key];

            // check required parameter(s)
            if (empty($params[$key]) && $isRequired == Param::REQUIRED) {
                $this->logger->critical(__('%1 is Required Parameter.', $key));
                return $error;
            } elseif (empty($params[$key]) && $isRequired == Param::CONDITIONALLY_REQUIRED) {
                if ($this->validateConditionalRequired($params, $key)) {
                    $this->logger->critical(__('%1 is Required Parameter.', $key));
                    return $error;
                }
                continue;
            } elseif (empty($params[$key]) && ($isRequired == Param::CONDITIONALLY_REQUIRED || $isRequired == Param::OPTIONAL)) {
                continue;
            }

            $value = $params[$key];

            // check over sized parameter(s)
            if (isset(Param::PARAMS_LENGTH[$key])) {
                $length = Param::PARAMS_LENGTH[$key];
                $result = $this->validateStringBytes($value, $length);
                if ($result) {
                    $this->logger->critical(__('Value on %1 exceeds the max length(%2 bytes)', $key, $length));
                    return $error;
                }
            }

            $value = $this->notIncludePrefixForCharValidation($key, $value);

            // check required length
            $length = $key == Param::PHONE_1 ? Param::PARAMS_RANGE[$key] : Param::PARAMS_LENGTH[$key];
            $result = $this->validateRequiredLength($value, $key, $length);
            if ($result) {
                if ($key == Param::PHONE_1) {
                    $length = $length['min'] . ' ~ ' . $length['max'];
                }
                if ($key == Param::PASSWORD) {
                    $length = $this->getMinimumPasswordLength() . ' ~ ' . $length;
                    $error = Result::INVALID_PASSWORD_LENGTH;
                }
                $this->logger->critical(__('Value on %1 does not match the required length(%2 bytes)', $key, $length));
                return $error;
            }

            // check allowed characters
            $num = Param::PARAMS_ALLOWED_CHARACTERS[$key];
            $result = $this->validateAllowedChar($value, $num, $key);
            if ($result) {
                $this->logger->critical(__('%1 Allowed Character: %2', $key, Param::ALLOWED_CHAR_LABEL[$num]));
                return $error;
            }

            if ($key == Param::COUNTRY_CODE && ($value == "" || $value != "JP")) {
                $this->logger->critical(__('%1 should be "JP".', $key));
                return $error;
            }

            if ($key == Param::DOB) {
                $result = $this->dateFormat($value);
                if (!$result) {
                    $this->logger->critical(__('%1 %2 Invalid Format: %3', $key, $value, "YYYYMMDD"));
                    return $error;
                }
            }

            if ($key == Param::PREFECTURE && !empty($value)) {
                if ($this->validateConditionalRequired($params, $key)) {
                    $this->logger->critical(__('%1 is Required Parameter or value is invalid.', $key));
                    return $error;
                }
            }

            //skip validation for phone1
            if (isset(Param::PARAMS_RANGE[$key]) && $key != Param::PHONE_1) {
                $range = Param::PARAMS_RANGE[$key];
                $result = $this->validateRange($value, $range);
                if ($result) {
                    $this->logger->critical(__('"%1" is an invalid %2 value', $value, $key));
                    return $error;
                }
            }
        }

        return $success;
    }

    public function validateRange($value, $range)
    {
        return ($value < $range['min'] || $value > $range['max']);
    }

    /**
     * Validate Characters
     *
     * @param  mixed $string
     * @param  mixed $type
     * @return void
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
            case Param::FULL_WIDTH_KANA_CHARACTERS:
                $result = !$this->isFullWidthKanaCharacters($string);
                break;
            case Param::ALPHA:
                $result = !$this->isAlpha($string);
                break;
            case Param::ALPHA_NUMERIC_SPACE:
                $result = !$this->isAlphaNumericSpace($string);
                break;
            case Param::FULL_HALF_WIDTH_CHARACTERS:
                // $string = $this->convertToNormalString($string);
                $string = $this->mbstrSplit($string);
                if ($key != Param::OCCUPATION) {
                    $result = (!$this->isAlphaNumericSpaceDash($string["halfWidth"]));
                } else {
                    $result = (!$this->isAlphaNumericSpace($string["halfWidth"]));
                }
                break;
            case Param::FULL_WIDTH_CHARACTERS:
                $result = !($this->isJapanese($string) || $this->isFullWidth($string));
                break;
        }
        return $result;
    }

    public function validateTransactionType($string)
    {
        return empty(Param::TRANSACTION_TYPE_VALUES[$string]);
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

    public function validateConditionalRequired($data, $param)
    {
        switch ($param) {
            case Param::POSTAL_CODE_1:
            case Param::POSTAL_CODE_2:
                if ((isset($data[Param::POSTAL_CODE_1]) && !empty($data[Param::POSTAL_CODE_1])) || (isset($data[Param::POSTAL_CODE_2]) && !empty($data[Param::POSTAL_CODE_2]))) {
                    return true;
                }
                break;
            case Param::PHONE_1:
            case Param::PHONE_2:
            case Param::PHONE_3:
                if ((isset($data[Param::PHONE_1]) && !empty($data[Param::PHONE_1])) || (isset($data[Param::PHONE_2]) && !empty($data[Param::PHONE_2])) || (isset($data[Param::PHONE_3]) && !empty($data[Param::PHONE_3]))) {
                    return true;
                }
                break;
            case Param::PREFECTURE:
                if (isset($data[Param::PREFECTURE]) && !empty($data[Param::PREFECTURE])) {
                    $countryCode = $data[Param::COUNTRY_CODE] ?? "JP";
                    $region = $this->customerHelper->getRegionIdByName(
                        $data[Param::PREFECTURE],
                        $countryCode
                    );
                    if (!$region) {
                        return true;
                    }
                }
                break;
        }
        return false;
    }

    public function convertToNormalString($string)
    {
        $string = mb_convert_kana($string, 'KVa');
        return $string;
    }

    public function isKanji($str)
    {
        return preg_match('/[\x{4E00}-\x{9FBF}]/u', $str) > 0;
    }

    public function isHiragana($str)
    {
        return preg_match('/[\x{3040}-\x{309F}]/u', $str) > 0;
    }

    public function isKatakana($str)
    {
        return preg_match('/[\x{30A0}-\x{30FF}]/u', $str) > 0;
    }

    public function isJapanese($str)
    {
        return $this->isKanji($str) || $this->isHiragana($str) || $this->isKatakana($str);
    }

    /**
     * Compares the length of the string
     *
     * @param  string $string
     * @return bool
     */
    public function isFullWidth($string)
    {
        return !(mb_strlen($string) === mb_strwidth($string));
    }

    /**
     * Not include for character validation
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function notIncludePrefixForCharValidation($key, $value)
    {
        if (isset(Param::INCLUDE_PREFIX[$key])) {
            $prefix = Param::INCLUDE_PREFIX[$key];
            return preg_replace("/^[$prefix]/", '', $value);
        }

        return $value;
    }

    /**
     * validate required length
     *
     * @param  string $value
     * @param  string $key
     * @param  int $length
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
            case Param::POSTAL_CODE_1:
            case Param::POSTAL_CODE_2:
            case Param::PHONE_2:
            case Param::PHONE_3:
                return $valueLength == $length ? false : true;
                break;
            case Param::PHONE_1:
                $range = Param::PARAMS_RANGE[$key];
                return $valueLength >= $range['min'] && $valueLength <= $range['max'] ? false : true;
        }
    }

    /**
     * Get minimum password length
     *
     * @return string
     * @since 100.1.0
     */
    public function getMinimumPasswordLength()
    {
        return $this->getConfigValue(AccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH);
    }
}
