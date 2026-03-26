<?php

namespace OnitsukaTigerVn\OnePay\Helper;

class Data extends \Ecomteck\OnePay\Helper\Data
{
    public function getDomesticCardAccessCode()
    {
        return $this->scopeConfig->getValue(
            self::ONEPAY_DOMESTIC_CARD_ACCESS_CODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }
}
