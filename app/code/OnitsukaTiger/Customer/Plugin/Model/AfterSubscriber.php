<?php
declare(strict_types=1);

namespace OnitsukaTiger\Customer\Plugin\Model;

use Exception;

/**
 * Set isSubscribed when subscribeCustomerById
 */
class AfterSubscriber {
    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $result
     * @return mixed
     * @throws Exception
     */
    public function afterSubscribeCustomerById(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        $result
    ) {
        $subscriber->setStatus($subscriber::STATUS_SUBSCRIBED)
            ->setStatusChanged(true)
            ->save();

        return $result;
    }
}
