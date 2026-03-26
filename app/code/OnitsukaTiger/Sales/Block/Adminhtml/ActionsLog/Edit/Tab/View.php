<?php

namespace OnitsukaTiger\Sales\Block\Adminhtml\ActionsLog\Edit\Tab;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Config\ScopeConfigInterface;

class View
{
    const ADMIN_TIME_ZONE_BE = "date_time/general/timezone_be";
    const ADMIN_LOCALE_BE = "date_time/general/locale_be";

    /**
     * @param TimezoneInterface $timezone
     * @param StoreManagerInterface $storeManager
     * @param State $state
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        TimezoneInterface           $timezone,
        StoreManagerInterface       $storeManager,
        State                       $state,
        ScopeConfigInterface        $scopeConfig
    ) {
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->state = $state;
        $this->scopeConfig = $scopeConfig;
    }

    public function afterGetLog(\Amasty\AdminActionsLog\Block\Adminhtml\ActionsLog\Edit\Tab\View $subject, $result)
    {
        $websiteId = $this->storeManager->getStore($result->getStoreId())->getWebsiteId();
        $timezoneBE = $this->scopeConfig->getValue(self::ADMIN_TIME_ZONE_BE, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websiteId);
        $localeBE = $this->scopeConfig->getValue(self::ADMIN_LOCALE_BE, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websiteId);
        if ($this->state->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            $dateTime = $this->timezone->formatDateTime(
                $result->getDateTime(),
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::MEDIUM,
                $localeBE,
                $timezoneBE
            );
            $result->setData("date_time", $dateTime);
        }
        return $result;
    }
}
