<?php

namespace OnitsukaTigerCpss\Crm\ViewModel;

use OnitsukaTigerCpss\Crm\Helper\HelperData;

class Customer implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @param HelperData $helperData
     */
    public function __construct(
        HelperData $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * Check module enable
     *
     * @return mixed
     */
    public function isEnableModule()
    {
        return $this->helperData->isEnableModule();
    }

    /**
     * Check agreement status customer
     *
     * @return bool
     */
    public function checkAgreement()
    {
        return $this->helperData->checkAgreement();
    }
}
