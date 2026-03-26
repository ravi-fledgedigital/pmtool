<?php

namespace Cpss\Crm\Helper;

use Cpss\Crm\Model\Shop\Config\Result;
use Cpss\Crm\Model\Shop\Config\Param;
use Magento\Framework\Encryption\Encryptor;
use Cpss\Crm\Model\RealStoreFactory as RealStore;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Cpss\Crm\Logger\Logger;

class Validation extends AbstractHelper
{
    protected $realStoreFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezoneInterface,
        Logger $logger,
        Encryptor $encryptor,
        RealStore $realStoreFactory
    ) {
        parent::__construct($scopeConfig, $timezoneInterface, $logger, $encryptor);

        $this->realStoreFactory = $realStoreFactory;
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
     * Validate Parameters
     *
     * @param  array $paramKeys
     * @param  array $params
     * @return int
     */
    public function validateParams($paramKeys, $params)
    {
        if (!$this->enabled()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                "message" => __('API endpoints are currently disabled. Please try again later.')
            ]);
            exit;
        }

        $error = Result::INVALID_PARAMS;
        $success = Result::SUCCESS;

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
            $length = Param::PARAMS_LENGTH[$key];
            $result = $this->validateStringBytes($value, $length);
            if ($result) {
                $this->logger->critical(__('Value on %1 exceeds the max length(%2 bytes)', $key, $length));
                if ($key == Param::START_DATE || $key == Param::END_DATE) {
                    return Result::INVALID_SPECIFIED_PERIOD;
                }
                return $error;
            }

            // check allowed characters
            $num = Param::PARAMS_ALLOWED_CHARACTERS[$key];
            $result = $this->validateAllowedChar($value, $num);
            if ($result) {
                $this->logger->critical(__('%1 Allowed Character: %2', $key, Param::ALLOWED_CHAR_LABEL[$num]));
                if ($key == Param::START_DATE || $key == Param::END_DATE) {
                    return Result::INVALID_SPECIFIED_PERIOD;
                }
                return $error;
            }

            // auth validation
            if ($key == Param::SITE_ID && $value != $this->getSiteId()) {
                $this->logger->critical(__('Invalid value on %1', $key));
                return Result::AUTH_FAILED;
            }

            // check transaction type values are the one specified
            if ($key === Param::TRANSACTION_TYPE && $this->validateTransactionType($value)) {
                $specified = implode(', ', array_keys(Param::TRANSACTION_TYPE_VALUES));
                $this->logger->critical(__('%1 only accepts %2 values', $key, $specified));
                return $error;
            }

            // Specific Parametter validations
            if ($key === Param::PURCHASE_ID) {
                $response = $this->validatePurchaseIdFormat($value, $params[Param::SHOP_ID]);
                if ($response !== true) {
                    $this->logger->critical($response);
                    return $error;
                }
            }

            // check start and end date format
            if ($key == Param::START_DATE || $key == Param::END_DATE) {
                if (!$this->dateFormat($value)) {
                    return Result::INVALID_SPECIFIED_PERIOD;
                }
            }
        }

        return $success;
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
        if ($count <> 4) {
            return __('%1 Invalid purchaseId format: 購入日付(8桁)_店舗ID(5桁)_POSレジ端末No.(4桁)_レシート番号(8桁) ', $purchaseId);
        }

        if (!$this->dateFormat($breakDown[0])) {
            return __('%1 Invalid Date format: YYYYMMDD', $breakDown[0]);
        }

        // if (($breakDown[1] <> str_pad($shopId, 5, '0', STR_PAD_LEFT))) {
        //     return __('%1 does not match shopId value %2', $breakDown[1], $shopId);
        // }

        if (strlen($breakDown[1]) <> 5 || strlen($breakDown[2]) <> 4 || strlen($breakDown[3]) <> 8) {
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
}
