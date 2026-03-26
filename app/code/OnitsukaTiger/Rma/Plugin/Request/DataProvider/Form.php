<?php
declare(strict_types=1);

namespace OnitsukaTiger\Rma\Plugin\Request\DataProvider;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Form
{
    const ADMIN_TIME_ZONE_BE = "date_time/general/timezone_be";
    const ADMIN_LOCALE_BE = "date_time/general/locale_be";

    /**
     * @var TimezoneInterface
     */
    private $timezone;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var State
     */
    private $state;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

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

    public function afterGetData(\Amasty\Rma\Model\Request\DataProvider\Form $subject, $result)
    {
        $items = $result["items"];
        $requestIds = [];
        foreach ($items as $item) {
            $requestIds[] = $item['request_id'];
            $storeId = $item["store_id"];
        }
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $listHistory = [];
        foreach($requestIds as $requestId) {
            $history = $result[$requestId]['history'];
            foreach ($history as $itemHistory) {
                $eventDateFormat = $this->timezone->date(new \DateTime($itemHistory["event_date"]))->format('Y-m-d H:i:s');
                $timezoneBE = $this->scopeConfig->getValue(self::ADMIN_TIME_ZONE_BE, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websiteId);
                $localeBE = $this->scopeConfig->getValue(self::ADMIN_LOCALE_BE, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websiteId);
                if ($this->state->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
                    $eventDateFormat = $this->timezone->formatDateTime(
                        $itemHistory["event_date"],
                        \IntlDateFormatter::SHORT,
                        \IntlDateFormatter::MEDIUM,
                        $localeBE,
                        $timezoneBE
                    );
                    $eventDateFormat =(new \DateTime($eventDateFormat))->format("Y/m/d H:i:s");
                }
                $itemHistory['event_date'] = $eventDateFormat;
                $listHistory[] = $itemHistory;
            }
            $result[$requestId]['history'] = $listHistory;
        }
        return $result;
    }
}
