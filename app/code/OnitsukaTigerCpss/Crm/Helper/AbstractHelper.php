<?php

namespace OnitsukaTigerCpss\Crm\Helper;
use Magento\Store\Model\ScopeInterface;

abstract class AbstractHelper extends \Cpss\Crm\Helper\AbstractHelper
{
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
