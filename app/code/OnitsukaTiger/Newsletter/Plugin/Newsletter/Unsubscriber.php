<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */

namespace OnitsukaTiger\Newsletter\Plugin\Newsletter;

use Aitoc\SendGrid\Model\ConfigProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\SubscriptionManager;

class Unsubscriber
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Aitoc\SendGrid\Model\ApiWork
     */
    private $apiWork;

    public function __construct(
        ConfigProvider $configProvider,
        \Aitoc\SendGrid\Model\ApiWork  $apiWork
    )
    {
        $this->configProvider = $configProvider;
        $this->apiWork = $apiWork;
    }

    /**
     * @param SubscriptionManager $subject
     * @param $email
     * @param $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    public function beforeSubscribe(
        SubscriptionManager $subject,
                            $email, $storeId
    ): array
    {

        if ($this->configProvider->isEnabled($storeId)) {
            $this->apiWork->sendNewSubscriber($email, $storeId);
        }

        return [$email, $storeId];
    }

    /**
     * @param SubscriptionManager $subscriber
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterSubscribeCustomer(
        SubscriptionManager $subscriber,
                            $result
    )
    {
        $storeId = $result->getStoreId();

        if ($this->configProvider->isEnabled($storeId)) {
            $this->apiWork->sendNewSubscriber($result->getSubscriberEmail(), $storeId);
        }
        return $result;
    }
}
