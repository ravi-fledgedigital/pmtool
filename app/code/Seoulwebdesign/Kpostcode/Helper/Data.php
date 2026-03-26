<?php

namespace Seoulwebdesign\Kpostcode\Helper;

use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function isEnable()
    {
        $scope = ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue("swdkpostcode/general/enable", $scope);
    }

    public function getSelectedVersion()
    {
        $scope = ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue("swdkpostcode/general/api_version", $scope);
    }

    public function getSelectedMode()
    {
        $scope = ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue("swdkpostcode/general/mode", $scope);
    }

    public function getPopupTitle()
    {
        $scope = ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue("swdkpostcode/general/popup_title", $scope);
    }

    public function getShowJibun()
    {
        $scope = ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue("swdkpostcode/general/show_jibun", $scope);
    }
}
