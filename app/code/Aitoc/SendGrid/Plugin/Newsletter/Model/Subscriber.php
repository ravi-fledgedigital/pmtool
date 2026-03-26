<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Plugin\Newsletter\Model;

class Subscriber
{
    /**
     * @var \Aitoc\SendGrid\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Aitoc\SendGrid\Model\ApiWork
     */
    private $apiWork;

    public function __construct(
        \Aitoc\SendGrid\Model\ConfigProvider $configProvider,
        \Aitoc\SendGrid\Model\ApiWork $apiWork,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->configProvider = $configProvider;
        $this->apiWork = $apiWork;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $email
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSubscribe(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        $email
    ) {
        $storeId = $subscriber->getStoreId();
        if ($this->configProvider->isEnabled($storeId)) {
            $this->apiWork->sendNewSubscriber($email, $storeId);
        }

        return [$email];
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeUnsubscribe(
        \Magento\Newsletter\Model\Subscriber $subscriber
    ) {
        $storeId = $subscriber->getStoreId();
        if ($this->configProvider->isEnabled($storeId)) {
            $this->apiWork->sendUnsubscribe($subscriber->getSubscriberEmail(), $storeId);
        }
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterUnsubscribeCustomerById(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        $result
    ) {
        $storeId = $subscriber->getStoreId();
        if ($this->configProvider->isEnabled($storeId)) {
            $this->apiWork->sendUnsubscribe($subscriber->getSubscriberEmail(), $storeId);
        }

        return $result;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterSubscribeCustomerById(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        $result
    ) {
        $storeId = $subscriber->getStoreId();
        if ($this->configProvider->isEnabled($storeId)) {
            $this->apiWork->sendNewSubscriber($subscriber->getSubscriberEmail(), $storeId);
        }

        return $result;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @return null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterDelete(
        \Magento\Newsletter\Model\Subscriber $subscriber
    ) {
        $storeId = $subscriber->getStoreId();
        if ($this->configProvider->isEnabled($storeId)) {
            $contacts = $this->apiWork->findContactsByEmails($subscriber->getSubscriberEmail(), $storeId);
            $ids = [];
            foreach ($contacts as $contact) {
                $ids[] = $contact['id'];
            }
            $this->apiWork->deleteCustomers($ids, $storeId);
        }

        return null;
    }
}
