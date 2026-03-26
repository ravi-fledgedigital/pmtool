<?php

namespace Cpss\Crm\Block\Adminhtml\Member;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Store\Model\StoreManagerInterface;

class Renderer extends AbstractRenderer
{

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        StoreManagerInterface $storemanager,
        private \Cpss\Crm\Block\Adminhtml\Member\Info $info,
        private \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->_storeManager = $storemanager;
        parent::__construct($context, $data);
        $this->_authorization = $context->getAuthorization();
    }
    /**
     * Renders grid column
     *
     * @param Object $row
     * @return  string
     */
    public function render(Object $row)
    {
        if (isset($row['operate']) && !empty($row['operate'])) {
            $webSiteId = $this->info->getWebsiteIdByCustomer();
            $convertedDate = $this->getLocalizedDateForWebsite(trim($row['operate_date']), $webSiteId);
            $row['operate'] = $convertedDate;
        }
        return $this->_getValue($row);
    }

    /**
     * Convert a UTC date to a specific website's timezone
     *
     * @param string $utcDate
     * @param int $websiteId
     * @return string
     */
    public function getLocalizedDateForWebsite($utcDate, $websiteId)
    {
        $inputDate = date('Y-m-d H:i:s', strtotime($utcDate));

        $websiteTimezone = $this->timezone->getConfigTimezone(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websiteId);
        $dateTime = new \DateTime($inputDate);

        return $this->timezone->date($dateTime)
            ->setTimezone(new \DateTimeZone($websiteTimezone))
            ->format('d-m-Y');
    }
}
