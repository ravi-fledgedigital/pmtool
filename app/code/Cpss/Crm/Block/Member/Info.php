<?php

namespace Cpss\Crm\Block\Member;

use Magento\Framework\View\Element\Template\Context;
use Cpss\JsBarcode\Block\Configuration;
use Cpss\Crm\Helper\Customer as CustomerHelper;
use Cpss\Crm\Model\CpssApiRequest;
use Cpss\Crm\Helper\Data;

class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * @var CustomerHelper
     */
    protected $customerHelper;
    protected $cpssApiRequest;
    protected $helperData;

    /**
     * @var \Cpss\Pos\Helper\Data
     */
    private $posHelper;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        CustomerHelper $customerHelper,
        CpssApiRequest $cpssApiRequest,
        Data $helperData,
        \Cpss\Pos\Helper\Data $posHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->customerHelper = $customerHelper;
        $this->cpssApiRequest = $cpssApiRequest;
        $this->helperData = $helperData;
        $this->posHelper = $posHelper;
        $this->timezone = $timezone;

        parent::__construct($context);
    }

    /**
     * Get Member ID
     *
     * @return string
     */
    public function getMemberId()
    {
        return $this->customerHelper->getMemberId();
    }

    public function getBarcode()
    {
        $memberId = str_pad($this->getMemberId(), 10, '0', STR_PAD_LEFT) . $this->getCurrentCountry();
        return $this->_layout->createBlock(Configuration::class)->loadBarcode($memberId);
    }

    /**
     * Call CPSS API get_member_status
     *
     * @return array
     */
    public function getMemberStatus()
    {
        $result = $this->cpssApiRequest->getMemberStatus($this->getMemberId());
        if (isset($result['X-CPSS-Result']) && $result['X-CPSS-Result'] == '000-000-000') {
            return json_decode($result['Body'][0][0], true);
        }

        return [];
    }

    /**
     * Validate Date
     *
     * @param string
     * @return Boolean
     */
    public function validateDate($date, $format = 'Y/m/d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d->getTimestamp();
    }

    public function isModuleEnabled()
    {
        return $this->customerHelper->isModuleEnabled();
    }

    public function getCurrentCountry()
    {
        return $this->helperData->getCountryCode();
    }

    /**
     * Call CPSS API get_nearest_expires
     *
     * @return array
     */
    public function getNearestExpires()
    {
        $result = $this->cpssApiRequest->getNearestExpires($this->getMemberId());
        if (isset($result['X-CPSS-Result']) && $result['X-CPSS-Result'] == '000-000-000') {
            return json_decode($result['Body'][0][0], true);
        }

        return [];
    }

    public function formatDateFromCpss($unixDate)
    {
        if (strlen($unixDate) >= 13) {
            $unixDate = substr($unixDate, 0, 10);
            $date = date("Y-m-d H:i:s",$unixDate);
            //return $this->posHelper->convertTimezone($date, "UTC", "d/m/y");
            return $this->timezone->date(new \DateTime($date))->format('d/m/y');
        }
        return null;
    }
}
