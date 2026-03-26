<?php

namespace OnitsukaTigerCpss\Crm\Model;

use Magento\Config\Model\Config\Source\Nooptreq;
use Magento\Store\Model\ScopeInterface;

class Order extends \Cpss\Crm\Model\Order
{
    /**
     * Get customer name
     *
     * @return string
     */
    public function getCustomerName()
    {
        if (null === $this->getCustomerFirstname()) {
            return (string)__('Guest');
        }

        $customerName = '';
        if ($this->isVisibleCustomerPrefix() && !empty($this->getCustomerPrefix())) {
            $customerName .= $this->getCustomerPrefix() . ' ';
        }
        $customerName .= ' ' . $this->getCustomerFirstname();
        if ($this->isVisibleCustomerSuffix() && !empty($this->getCustomerSuffix())) {
            $customerName .= ' ' . $this->getCustomerSuffix();
        }
        $customerName .= $this->getCustomerLastname();
        if ($this->isVisibleCustomerMiddlename() && !empty($this->getCustomerMiddlename())) {
            $customerName .= ' ' . $this->getCustomerMiddlename();
        }

        return $customerName;
    }

    /**
     * Is visible customer middlename
     *
     * @return bool
     */
    private function isVisibleCustomerMiddlename(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'customer/address/middlename_show',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Is visible customer prefix
     *
     * @return bool
     */
    private function isVisibleCustomerPrefix(): bool
    {
        $prefixShowValue = $this->scopeConfig->getValue(
            'customer/address/prefix_show',
            ScopeInterface::SCOPE_STORE
        );

        return $prefixShowValue !== Nooptreq::VALUE_NO;
    }

    /**
     * Is visible customer suffix
     *
     * @return bool
     */
    private function isVisibleCustomerSuffix(): bool
    {
        $prefixShowValue = $this->scopeConfig->getValue(
            'customer/address/suffix_show',
            ScopeInterface::SCOPE_STORE
        );

        return $prefixShowValue !== Nooptreq::VALUE_NO;
    }
}