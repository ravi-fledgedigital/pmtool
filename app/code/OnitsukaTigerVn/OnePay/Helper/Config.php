<?php

namespace OnitsukaTigerVn\OnePay\Helper;

use Magento\Framework\App\Helper\Context;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ONEPAY_DOMESTIC_CARD_PAYMENT_URL
        = 'payment/onepay_domestic/payment_url';

    const ONEPAY_DOMESTIC_CARD_ACCESS_CODE
        = 'payment/onepay_domestic/access_code';

    const ONEPAY_DOMESTIC_CARD_MERCHANT_ID
        = 'payment/onepay_domestic/merchant_id';

    const ONEPAY_DOMESTIC_CARD_HASH_CODE
        = 'payment/onepay_domestic/hash_code';

    const ONEPAY_DOMESTIC_CARD_QUERYDR_URL
        = 'payment/onepay_domestic/querydr_url';

    const ONEPAY_DOMESTIC_CARD_QUERYDR_USER
        = 'payment/onepay_domestic/querydr_user';

    const ONEPAY_DOMESTIC_CARD_QUERYDR_PASSWORD
        = 'payment/onepay_domestic/querydr_password';

    const ONEPAY_DOMESTIC_CARD_ORDER_PREFIX
        = 'payment/onepay_domestic/order_prefix';

    const ONEPAY_INTERNATIONAL_CARD_PAYMENT_URL
        = 'payment/onepay_international/payment_url';

    const ONEPAY_INTERNATIONAL_CARD_ACCESS_CODE
        = 'payment/onepay_international/access_code';

    const ONEPAY_INTERNATIONAL_CARD_MERCHANT_ID
        = 'payment/onepay_international/merchant_id';

    const ONEPAY_INTERNATIONAL_CARD_HASH_CODE
        = 'payment/onepay_international/hash_code';

    const ONEPAY_INTERNATIONAL_CARD_QUERYDR_URL
        = 'payment/onepay_international/querydr_url';

    const ONEPAY_INTERNATIONAL_CARD_QUERYDR_USER
        = 'payment/onepay_international/querydr_user';

    const ONEPAY_INTERNATIONAL_CARD_QUERYDR_PASSWORD
        = 'payment/onepay_international/querydr_password';

    const ONEPAY_INTERNATIONAL_CARD_ORDER_PREFIX
        = 'payment/onepay_domestic/order_prefix';

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context       $context
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Store\Model\StoreManagerInterface  $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->localeResolver = $localeResolver;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Retrieve the OnePay Domestic card payment URL
     *
     * @return string
     */
    public function getDomesticCardPaymentUrl($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_DOMESTIC_CARD_PAYMENT_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay Domestic card access code
     *
     * @return string
     */
    public function getDomesticCardAccessCode($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_DOMESTIC_CARD_ACCESS_CODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay Domestic card merchant id
     *
     * @return string
     */
    public function getDomesticCardMerchantId($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_DOMESTIC_CARD_MERCHANT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay Domestic card hash code
     *
     * @return string
     */
    public function getDomesticCardHashCode($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_DOMESTIC_CARD_HASH_CODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay Domestic card QueryDR URL
     *
     * @return string
     */
    public function getDomesticCardQueryDrUrl($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_DOMESTIC_CARD_QUERYDR_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay Domestic card QueryDR user
     *
     * @return string
     */
    public function getDomesticCardQueryDrUser($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_DOMESTIC_CARD_QUERYDR_USER,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay Domestic card QueryDR password
     *
     * @return string
     */
    public function getDomesticCardQueryDrPassword($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_DOMESTIC_CARD_QUERYDR_PASSWORD,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay Domestic card payment URL
     *
     * @return string
     */
    public function getDomesticCardOrderPrefix($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_DOMESTIC_CARD_ORDER_PREFIX,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay International card payment URL
     *
     * @return string
     */
    public function getInternationalCardPaymentUrl($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_INTERNATIONAL_CARD_PAYMENT_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay International card access code
     *
     * @return string
     */
    public function getInternationalCardAccessCode($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_INTERNATIONAL_CARD_ACCESS_CODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay International card merchant id
     *
     * @return string
     */
    public function getInternationalCardMerchantId($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_INTERNATIONAL_CARD_MERCHANT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay International card hash code
     *
     * @return string
     */
    public function getInternationalCardHashCode($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_INTERNATIONAL_CARD_HASH_CODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay International card QueryDR URL
     *
     * @return string
     */
    public function getInternationalCardQueryDrUrl($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_INTERNATIONAL_CARD_QUERYDR_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay International card QueryDR user
     *
     * @return string
     */
    public function getInternationalCardQueryDrUser($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_INTERNATIONAL_CARD_QUERYDR_USER,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay International card QueryDR password
     *
     * @return string
     */
    public function getInternationalCardQueryDrPassword($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_INTERNATIONAL_CARD_QUERYDR_PASSWORD,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieve the OnePay International card payment URL
     *
     * @return string
     */
    public function getInternationalCardOrderPrefix($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_INTERNATIONAL_CARD_ORDER_PREFIX,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }
}
