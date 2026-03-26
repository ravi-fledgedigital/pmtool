<?php

namespace Vaimo\OTAdobeDataLayer\Observer;

use Magento\Framework\Event\ObserverInterface;

class RegisterSuccess implements ObserverInterface
{
	/**
     * @var \Vaimo\OTAdobeDataLayer\Helper\Data
     */
    protected $dataLayerHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

	/**
     * customer registration constructor.
     * @param \Vaimo\OTAdobeDataLayer\Helper\Data $dataLayerHelper
     */
    public function __construct(
        \Vaimo\OTAdobeDataLayer\Helper\Data $dataLayerHelper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->dataLayerHelper = $dataLayerHelper;
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->dataLayerHelper->isEnabledAdobeLaunch()){
            $customer = $observer->getEvent()->getCustomer();
            $isSubscribed = $this->request->getParams();

            $isSubscribedStatus = '';
            if(isset($isSubscribed['is_subscribed']) && $isSubscribed['is_subscribed'] == 1){
                $isSubscribedStatus = $isSubscribed['is_subscribed'];
                $this->dataLayerHelper->setIsSubscribedData($isSubscribedStatus);
            }
            $customData = [
                'loggedin' => true,
                'customer_id' => $customer->getId(),
                'is_subscribed' => $isSubscribedStatus
            ];

            $this->dataLayerHelper->getSignedUpCustomerData($customData);
        }
    }
}