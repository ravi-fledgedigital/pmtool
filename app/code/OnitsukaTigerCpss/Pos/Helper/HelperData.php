<?php

namespace OnitsukaTigerCpss\Pos\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTigerCpss\Crm\Helper\MemberValidation;

/**
 * Class Helper Data Pos
 */
class HelperData extends AbstractHelper
{
    const ORDER_STATUS_DELIVERED = 'delivered';
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $customerSession,
        private \OnitsukaTigerCpss\Crm\Helper\HelperData $cpssHelperData,
        private \Magento\Store\Model\StoreManagerInterface $storeManager,
        private \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        private \Cpss\Pos\Helper\Data $posHelperData
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
    }

    /**
     * Check module enable
     *
     * @return mixed
     */
    public function isEnableModule($scopeCode = null)
    {
        return $this->scopeConfig->getValue(\Cpss\Crm\Helper\Data::CRM_ENABLED_PATH, ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    private function getCurrencyCodeByStoreCode($storeCode)
    {
        $storeIds = $this->cpssHelperData->getStoreIds();
        $storeId = $storeIds[$storeCode] ?? null;

        $currency = 'SGD';

        if ($storeId) {
            $store = $this->storeManager->getStore($storeId);
            if ($store && $store->getId()) {
                $currency = $store->getDefaultCurrencyCode();
            }
        }

        return ['storeId' => $storeId, 'currency' => $currency];
    }

    public function getFormatedPrice($price, $storeCode)
    {
        $currencyData = $this->getCurrencyCodeByStoreCode($storeCode);

        $storeId = $currencyData['storeId'];
        $currency = $currencyData['currency'];
        $precision = 2;

        if ($storeCode == 'kr') {
            $precision = 0;
        }

        return $this->priceCurrency->convertAndFormat(
            $price,
            $includeContainer = true,
            $precision,
            $storeId,
            $currency
        );

    }

    public function getLocaleByStoreCode($storeCode)
    {
        if (empty($storeCode)) {
            return 'UTC';
        }
        $websiteId = array_search(strtoupper($storeCode), MemberValidation::WEBSITE_COUNTRY_CODE);
        return $this->scopeConfig->getValue('general/locale/timezone', ScopeInterface::SCOPE_WEBSITES, $websiteId);
    }

    public function formatDateFromCpss($date, $storeCode)
    {
        $locale = $this->getLocaleByStoreCode($storeCode);
        $date = date("Y-m-d H:i:s", strtotime($date));
        return $this->posHelperData->convertTimezone($date, "$locale", "Y/m/d H:i:s");
    }
}
