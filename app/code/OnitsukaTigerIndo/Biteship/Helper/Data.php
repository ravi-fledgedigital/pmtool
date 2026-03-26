<?php

namespace OnitsukaTigerIndo\Biteship\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 * OnitsukaTigerIndo\Biteship\Helper\Data
 */
class Data extends AbstractHelper
{
    public const PATH_ENABLE = 'biteship_indo/biteship_api_urls/enable';

    public const BITESHIP_AUTH_KEY = 'biteship_indo/biteship_api_urls/authorization_key';

    public const BITESHIP_ORDER_API = 'biteship_indo/biteship_api_urls/order_api_url';

    public const BITESHIP_COURIER_RATE_API = 'biteship_indo/biteship_api_urls/courier_rate_url';

    public const BITESHIP_COURIER_ORIGIN_POSTAL_CODE = 'biteship_indo/biteship_api_urls/origin_postal_code';

    public const BITESHIP_ORIGIN_ADDRESS_CONTACT_NAME = 'biteship_indo/biteship_origin_address/contact_name';

    public const BITESHIP_ORIGIN_ADDRESS_CONTACT_PHONE = 'biteship_indo/biteship_origin_address/contact_phone';

    public const BITESHIP_ORIGIN_ADDRESS_ADDRESS = 'biteship_indo/biteship_origin_address/address';

    public const BITESHIP_ORIGIN_ADDRESS_POSTAL_CODE = 'biteship_indo/biteship_origin_address/postal_code';

    public const BITESHIP_MINIMUM_PRICE_FOR_SHIPPING_RATE =
        'biteship_indo/biteship_api_urls/below_this_price_shipping_fee_applied';

    public const WEBSITE_ID = 5;

    const BITESHIP_INDO_BITESHIP_API_URLS_DAYS = 'biteship_indo/biteship_api_urls/days';

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->_curl = $curl;
        parent::__construct($context);
    }

    /**
     * Module Enable
     *
     * @return mixed
     */
    public function isEnableModule()
    {
        return $this->scopeConfig->getValue(self::PATH_ENABLE, ScopeInterface::SCOPE_WEBSITE, self::WEBSITE_ID);
    }

    /**
     * Get biteship auth token
     *
     * @return string
     */
    public function getBiteshipAuthKey()
    {
        return $this->scopeConfig->getValue(self::BITESHIP_AUTH_KEY, ScopeInterface::SCOPE_WEBSITE, self::WEBSITE_ID);
    }

    /**
     * Get biteship Order Api
     *
     * @return string
     */
    public function getBiteshipOrderApi()
    {
        return $this->scopeConfig->getValue(self::BITESHIP_ORDER_API, ScopeInterface::SCOPE_WEBSITE, self::WEBSITE_ID);
    }

    /**
     * Get biteship courier rate Api
     *
     * @return string
     */
    public function getBiteshipCourierRateApi()
    {
        return $this->scopeConfig->getValue(
            self::BITESHIP_COURIER_RATE_API,
            ScopeInterface::SCOPE_WEBSITE,
            self::WEBSITE_ID
        );
    }

    /**
     * Get origin postal code courier rate Api
     *
     * @return string
     */
    public function getOriginPostalCodeForCourierRateApi()
    {
        return $this->scopeConfig->getValue(
            self::BITESHIP_COURIER_ORIGIN_POSTAL_CODE,
            ScopeInterface::SCOPE_WEBSITE,
            self::WEBSITE_ID
        );
    }

    /**
     * Get minimum price for courier rate Api
     *
     * @return string
     */
    public function getMinimumPriceForCourierRateApi()
    {
        return $this->scopeConfig->getValue(
            self::BITESHIP_MINIMUM_PRICE_FOR_SHIPPING_RATE,
            ScopeInterface::SCOPE_WEBSITE,
            self::WEBSITE_ID
        );
    }

    /**
     * Get biteship origin contact name
     *
     * @return string
     */
    public function getBiteshipOriginContactName()
    {
        return $this->scopeConfig->getValue(self::BITESHIP_ORIGIN_ADDRESS_CONTACT_NAME, ScopeInterface::SCOPE_WEBSITE, self::WEBSITE_ID);
    }

    /**
     * Get biteship origin contact name
     *
     * @return string
     */
    public function getBiteshipOriginContactPhone()
    {
        return $this->scopeConfig->getValue(self::BITESHIP_ORIGIN_ADDRESS_CONTACT_PHONE, ScopeInterface::SCOPE_WEBSITE, self::WEBSITE_ID);
    }

    /**
     * Get biteship origin contact name
     *
     * @return string
     */
    public function getBiteshipOriginAddress()
    {
        return $this->scopeConfig->getValue(self::BITESHIP_ORIGIN_ADDRESS_ADDRESS, ScopeInterface::SCOPE_WEBSITE, self::WEBSITE_ID);
    }

    /**
     * Get biteship origin contact name
     *
     * @return string
     */
    public function getBiteshipOriginPostalCode()
    {
        return $this->scopeConfig->getValue(self::BITESHIP_ORIGIN_ADDRESS_POSTAL_CODE, ScopeInterface::SCOPE_WEBSITE, self::WEBSITE_ID);
    }

    /**
     * Gets the curl call.
     *
     * @param      String $apiUrl  The api url
     * @param      mixed  $data    The data
     *
     * @return     mixed  The curl call.
     */
    public function getCurlCall($apiUrl, $data)
    {
        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->_curl->addHeader("Content-Type", "application/json");
        $this->_curl->addHeader("authorization", $this->getBiteshipAuthKey());
        $this->_curl->post($apiUrl, $data);
        $response = $this->_curl->getBody();
        return $response;
    }

    /**
     * Get biteship order synced days
     *
     * @return string
     */
    public function getBiteshipDaysConfigured()
    {
        return $this->scopeConfig->getValue(self::BITESHIP_INDO_BITESHIP_API_URLS_DAYS, ScopeInterface::SCOPE_WEBSITE, self::WEBSITE_ID);
    }
}
