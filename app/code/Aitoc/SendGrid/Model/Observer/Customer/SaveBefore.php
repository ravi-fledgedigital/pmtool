<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Model\Observer\Customer;

class SaveBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Aitoc\SendGrid\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var \Aitoc\SendGrid\Model\ApiWork
     */
    private $apiWork;

    public function __construct(
        \Aitoc\SendGrid\Model\ConfigProvider $configProvider,
        \Aitoc\SendGrid\Model\ApiWork $apiWork,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {
        $this->configProvider = $configProvider;
        $this->subscriberFactory = $subscriberFactory;
        $this->apiWork = $apiWork;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();
        $storeId  = $customer->getStoreId();
        if ($this->configProvider->isEnabled($storeId)) {
            $subscriber = $this->subscriberFactory->create();
            $subscriber->loadByEmail($customer->getEmail());
            if ($subscriber->getEmail() !== $customer->getEmail()) {
                $this->apiWork->sendCustomerWithoutSub([$customer], $storeId);
            }
        }
    }
}
