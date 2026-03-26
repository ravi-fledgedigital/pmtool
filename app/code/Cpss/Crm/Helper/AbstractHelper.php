<?php

namespace Cpss\Crm\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Cpss\Crm\Logger\Logger;
use Magento\Framework\Encryption\Encryptor;
use Magento\Store\Model\ScopeInterface;

abstract class AbstractHelper
{
    const CRM_ENABLED_PATH = 'crm/general/enable';
    const CRM_SITE_ID_PATH = 'crm/general/site_id';
    const CRM_SHOP_ID_PATH = 'crm/shop/shop_id';
    const CRM_SHOP_NAME_PATH = 'crm/shop/shop_name';
    const CRM_SALT_PATH = 'crm/shop/salt';
    const CRM_SHOP_PASS_PATH = 'crm/shop/shop_pass';
    const CRM_SHOP_STATUS_PATH = 'crm/shop/shop_status';
    const CRM_CPSS_SHOP_ID = 'crm/general/shop_id';
    const MEMBER_ID_PREFIX = 'crm/general/cpssmemberid_prefix';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @param ScopeConfigInterface
     * @param TimezoneInterface
     * @param Logger
     *
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezoneInterface,
        Logger $logger,
        Encryptor $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->timezone = $timezoneInterface;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
    }

    public function enabled()
    {
        return $this->getConfigValue(self::CRM_ENABLED_PATH);
    }

    public function getSiteId()
    {
        return $this->getConfigValue(self::CRM_SITE_ID_PATH);
    }

    public function getShopId()
    {
        return $this->getConfigValue(self::CRM_SHOP_ID_PATH);
    }

    public function getShopName()
    {
        return $this->getConfigValue(self::CRM_SHOP_NAME_PATH);
    }

    public function getShopStatus()
    {
        return $this->getConfigValue(self::CRM_SHOP_STATUS_PATH);
    }

    public function getShopPass()
    {
        return $this->getConfigValue(self::CRM_SHOP_PASS_PATH);
    }

    public function getSalt()
    {
        return $this->getConfigValue(self::CRM_SALT_PATH);
    }

    public function getCpssShopId($storeID = '')
    {
        if (!empty($storeID)) {
            return $this->getConfigValue(self::CRM_CPSS_SHOP_ID, $storeID);
        }

        return $this->getConfigValue(self::CRM_CPSS_SHOP_ID);
    }

    public function getCpssMembeIdPrefix()
    {
        return $this->getConfigValue(self::MEMBER_ID_PREFIX);
    }

    public function convertArrayValuesToString(&$array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->convertArrayValuesToString($value);
            } else {
                if ($key != "resultCode") {
                    $value = "{$value}";
                }
            }
        }
        return $array;
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
}
