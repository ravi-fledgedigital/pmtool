<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Model;

use Magento\Newsletter\Model\Subscriber;

class SyncContacts
{
    /**
     * @var ApiWork
     */
    private $apiWork;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    private $customerFactory;

    /**
 * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
 */
    private $subscriberCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection
     */
    private $subscriberCollection;

    public function __construct(
        \Aitoc\SendGrid\Model\ApiWork $apiWork,
        \Aitoc\SendGrid\Model\ConfigProvider $configProvider,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->apiWork = $apiWork;
        $this->configProvider = $configProvider;
        $this->customerFactory = $customerFactory;
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }
    
    public function sync()
    {
        try {
            $subscriberEmails = $this->sendSubscribeCustomers();
            $unsubscribeEmails = $this->sendUnsubscribeCustomers();
            $this->sendNewCustomers($subscriberEmails, $unsubscribeEmails);
            $this->syncUnsubscribeFromGrid();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @return array
     */
    private function sendSubscribeCustomers()
    {
        $this->subscriberCollection = $this->subscriberCollectionFactory
            ->create()
            ->addFieldToFilter("subscriber_status", Subscriber::STATUS_SUBSCRIBED);
        $subscriberEmails = $this->getSubscribeEmails($this->subscriberCollection);
        foreach ($subscriberEmails as $storeId => $storeEmails) {
            $this->apiWork->sendNewSubscriber($storeEmails, $storeId);
        }

        return $subscriberEmails;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendUnsubscribeCustomers()
    {
        $unsubscribeCollection = $this->subscriberCollectionFactory
            ->create()
            ->addFieldToFilter("subscriber_status", Subscriber::STATUS_UNSUBSCRIBED);

        $unsubscribeEmails = $this->getSubscribeEmails($unsubscribeCollection);
        foreach ($unsubscribeEmails as $storeId => $storeEmails) {
            $this->apiWork->sendUnsubscribe($storeEmails, $storeId);
        }

        return $unsubscribeEmails;
    }

    /**
     * @param $subscriberEmails
     * @param $unsubscribeEmails
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendNewCustomers($subscriberEmails, $unsubscribeEmails)
    {
        $stores = $this->storeManager->getStores();
        foreach ($stores as $id => $store) {
            if ($this->configProvider->isAddCustomerWithoutSubscribe($id)) {
                $customerCollection = $this->customerFactory->create();
                $customerCollection->addAttributeToFilter("store_id", $id);
                $customersWithoutSub = [];
                foreach ($customerCollection as $customer) {
                    $isInSubscribers = $subscriberEmails
                        && in_array($customer->getEmail(), $subscriberEmails[$customer->getStoreId()]);
                    $isInUnsubscribe = $unsubscribeEmails
                        && in_array($customer->getEmail(), $unsubscribeEmails[$customer->getStoreId()]);
                    if (!$isInSubscribers && !$isInUnsubscribe) {
                        $customersWithoutSub[] = $customer;
                    }
                }
                $this->apiWork->sendCustomerWithoutSub($customersWithoutSub, $id);
            }
        }
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function syncUnsubscribeFromGrid()
    {
        $unsubscribeCustomers = $this->apiWork->getAllUnsubscribe();

        $magentoEmails = [];
        foreach ($this->subscriberCollection as $magentoSubscribeCustomer) {
            $magentoEmails[] = $magentoSubscribeCustomer->getSubscriberEmail();
        }
        foreach ($unsubscribeCustomers as $customer) {
            if (in_array($customer['email'], $magentoEmails)) {
                $subscriber = $this->subscriberCollectionFactory->create()
                    ->getItemByColumnValue('subscriber_email', $customer['email']);
                $subscriber->setStatus(Subscriber::STATUS_UNSUBSCRIBED);
                $subscriber->save();
            }
        }
    }

    /**
     * @return array
     */
    private function getSubscribeEmails($collection)
    {
        $subscribeEmails = [];
        foreach ($collection as $subscriber) {
            $subscribeEmails[$subscriber->getStoreId()][] = $subscriber->getSubscriberEmail();
        }

        return $subscribeEmails;
    }
}
