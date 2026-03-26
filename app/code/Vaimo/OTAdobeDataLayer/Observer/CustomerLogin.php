<?php

namespace Vaimo\OTAdobeDataLayer\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerLogin implements ObserverInterface
{
	/**
     * @var \Vaimo\OTAdobeDataLayer\Helper\Data
     */
    protected $dataLayerHelper;

	/**
     * customer login constructor.
     * @param \Vaimo\OTAdobeDataLayer\Helper\Data $dataLayerHelper
     */
    public function __construct(
        \Vaimo\OTAdobeDataLayer\Helper\Data $dataLayerHelper,
    ) {
        $this->dataLayerHelper = $dataLayerHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if($this->dataLayerHelper->isEnabledAdobeLaunch()){
            $customer = $observer->getEvent()->getCustomer();
            $customData = [
                'loggedin' => true,
                'customer_id' => $customer->getId(),
                'customer_name' => $customer->getName()
            ];

            $this->dataLayerHelper->getLoggedInCustomerData($customData);
        }

    }
}