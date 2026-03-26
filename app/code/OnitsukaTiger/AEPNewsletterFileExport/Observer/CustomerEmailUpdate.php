<?php

namespace OnitsukaTiger\AEPNewsletterFileExport\Observer;

use OnitsukaTiger\AEPNewsletterFileExport\Model\ResourceModel\Subscriber as SubscriberResourceModel;
use OnitsukaTiger\AEPNewsletterFileExport\Model\Subscriber;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Newsletter\Model\ResourceModel\Subscriber as CoreSubscriberResourceModel;
use Magento\Newsletter\Model\SubscriberFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Session\SessionManagerInterface;

class CustomerEmailUpdate implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var SubscriberFactory
     */
    private SubscriberFactory $subscriberFactory;

    /**
     * @var Subscriber
     */
    private Subscriber $subscriberModel;

    /**
     * @var SubscriberResourceModel
     */
    private SubscriberResourceModel $subscriberResourceModel;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var CoreSubscriberResourceModel
     */
    private CoreSubscriberResourceModel $coreSubscriberResourceModel;

    protected $session;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param SubscriberFactory $subscriberFactory
     * @param Subscriber $subscriberModel
     * @param SubscriberResourceModel $subscriberResourceModel
     * @param DateTime $dateTime
     * @param CoreSubscriberResourceModel $coreSubscriberResourceModel
     */
    public function __construct(
        LoggerInterface $logger,
        SubscriberFactory $subscriberFactory,
        Subscriber $subscriberModel,
        SubscriberResourceModel $subscriberResourceModel,
        DateTime $dateTime,
        CoreSubscriberResourceModel $coreSubscriberResourceModel,
        SessionManagerInterface $session
    ) {
        $this->logger = $logger;
        $this->subscriberFactory = $subscriberFactory;
        $this->subscriberModel = $subscriberModel;
        $this->subscriberResourceModel = $subscriberResourceModel;
        $this->dateTime = $dateTime;
        $this->coreSubscriberResourceModel = $coreSubscriberResourceModel;
        $this->session = $session;
    }

    /**
     * Customer email update
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer_data_object');
        $origCustomerDataObject = $observer->getData('orig_customer_data_object') ?? null;
        $newEmail = $customer->getEmail();

        if ($origCustomerDataObject && $origCustomerDataObject->getEmail() !== $newEmail) {
            $customerId = $customer->getId();

            $subscribers = $this->subscriberFactory->create()->getCollection()
                ->addFieldToFilter('customer_id', $customerId);

            foreach ($subscribers as $subscriber) {
                try {
                    if ($subscriber->getId()) {
                        $subscriberData = $subscriber->getData();
                        $changeStatusDateTime = $this->dateTime->gmtDate();
                        $coreSubscriberData = [
                            'subscriber_id' => $subscriber->getId(),
                            'subscriber_email' => $newEmail,
                            'change_status_at' => $changeStatusDateTime
                        ];
                        unset($subscriberData['subscriber_id']);
                        $subscriberData['change_status_at'] = $changeStatusDateTime;
                        $subscriberData['subscriber_status'] = \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED; //phpcs:ignore

                        $this->subscriberResourceModel->save($this->subscriberModel->setData($subscriberData));
                        $this->coreSubscriberResourceModel->save($subscriber->setData($coreSubscriberData)); //phpcs:ignore
                    }
                } catch (\Exception $e) {
                    $this->logger->error(__("Something went wrong while adding customer newsletter data to backup table: " . $e->getMessage())); //phpcs:ignore
                }
            }
        }
    }
}
