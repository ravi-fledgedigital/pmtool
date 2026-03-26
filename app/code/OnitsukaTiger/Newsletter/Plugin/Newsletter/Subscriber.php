<?php
declare(strict_types=1);
namespace OnitsukaTiger\Newsletter\Plugin\Newsletter;

use Magento\Framework\Exception\NoSuchEntityException;

class Subscriber {

    /**
     * @var \Aitoc\SendGrid\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Aitoc\SendGrid\Model\ApiWork
     */
    private $apiWork;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;


    /**
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Aitoc\SendGrid\Model\ConfigProvider $configProvider
     * @param \Aitoc\SendGrid\Model\ApiWork $apiWork
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Aitoc\SendGrid\Model\ConfigProvider $configProvider,
        \Aitoc\SendGrid\Model\ApiWork $apiWork,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->configProvider = $configProvider;
        $this->apiWork = $apiWork;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @throws NoSuchEntityException
     */
    public function beforeUnsubscribe(
        \Magento\Newsletter\Model\Subscriber $subscriber
    ) {
        $storeId = $subscriber->getStoreId();
        if ($this->configProvider->isEnabled($storeId)) {
            $this->deleteSubscriberEmailExist($subscriber, $storeId);
        }
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterUnsubscribeCustomerById(
        \Magento\Newsletter\Model\Subscriber $subscriber,
                                             $result
    ) {
        $storeId = $subscriber->getStoreId();
        if ($this->configProvider->isEnabled($storeId)) {
            $this->deleteSubscriberEmailExist($subscriber, $storeId);
        }

        return $result;
    }

    /**
     * @param $subscriber
     * @param $storeId
     * @throws NoSuchEntityException
     */
    protected  function deleteSubscriberEmailExist($subscriber, $storeId) {
        try {
            $contacts = $this->apiWork->findContactsByEmails($subscriber->getSubscriberEmail(), $storeId);
            $ids = [];
            foreach ($contacts as $contact) {
                $ids[] = $contact['id'];
            }
            $this->apiWork->deleteCustomers($ids, $storeId);
        }catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

}
