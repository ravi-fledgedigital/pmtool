<?php

declare(strict_types=1);

namespace OnitsukaTiger\AEPNewsletterFileExport\Model\Export;

use OnitsukaTiger\AEPNewsletterFileExport\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use OnitsukaTiger\AEPNewsletterFileExport\Model\Subscriber;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FlagManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Model\Export\Factory as CollectionFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Vaimo\AEPNewsletterFileExport\Model\Export\Mapping;

class NewsletterSubscribers extends \Vaimo\AEPNewsletterFileExport\Model\Export\NewsletterSubscribers
{
    private const PAGE_SIZE = 500;
    public const LAST_RUN_FLAG_CODE = 'aep_newsletter_subscribers_export_last_run';

    private const UPDATED_AT_DATE_FORMAT = 'Y-m-d\TH:i:s.Z\Z';

    /**
     * @var FlagManager
     */
    private FlagManager $flagManager;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var SubscriberCollectionFactory
     */
    private SubscriberCollectionFactory $subscriberCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param CollectionByPagesIteratorFactory $iteratorFactory
     * @param Mapping $mapping
     * @param FlagManager $flagManager
     * @param DateTime $dateTime
     * @param SubscriberCollectionFactory $subscriberCollectionFactory
     * @param LoggerInterface $logger
     * @param mixed[] $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ScopeConfigInterface             $scopeConfig,
        StoreManagerInterface            $storeManager,
        CollectionFactory                $collectionFactory,
        CollectionByPagesIteratorFactory $iteratorFactory,
        Mapping                          $mapping,
        FlagManager                      $flagManager,
        DateTime                         $dateTime,
        SubscriberCollectionFactory      $subscriberCollectionFactory,
        LoggerInterface                  $logger,
        array                            $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $storeManager,
            $collectionFactory,
            $iteratorFactory,
            $mapping,
            $flagManager,
            $dateTime,
            $data
        );
        $this->flagManager = $flagManager;
        $this->collectionFactory = $collectionFactory;
        $this->dateTime = $dateTime;
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Export newsletter
     *
     * @return void
     */
    public function export(): void
    {
        $this->appendUpdatedCustomerEmailInNewsletter();
        $this->_byPagesIterator->iterate(
            $this->_getEntityCollection(),
            self::PAGE_SIZE,
            [[$this, 'exportItem']]
        );

        $this->flagManager->saveFlag(self::LAST_RUN_FLAG_CODE, $this->dateTime->date());
    }

    /**
     *  Append updated customer email in newsletter
     *
     * @return void
     */
    public function appendUpdatedCustomerEmailInNewsletter()
    {
        $subscribers = $this->subscriberCollectionFactory->create();

        if ($subscribers->getSize()) {
            foreach ($subscribers as $subscriber) {
                try {
                    $this->exportSubscriber($subscriber);
                    $subscriber->delete();
                } catch (\Exception $e) {
                    $this->logger->error("Something went wrong while exporting newsletter for updated customer email => {$subscriber->getSubscriberEmail()}, Reason => {$e->getMessage()}"); //phpcs:ignore
                }
            }
        }
    }

    /**
     * Export subscriber
     *
     * @param Subscriber $subscriber
     * @return void
     * @throws LocalizedException
     */
    public function exportSubscriber($subscriber)
    {
        $result = [];
        $result['storeID'] = $subscriber->getStoreId();
        $result['storeCode'] = $subscriber->getStoreCode();
        $result['Customer_ID'] = $this->getModifiedCustomerId($subscriber->getCustomerId());
        $result['subscriberEmail'] = $subscriber->getSubscriberEmail();
        $result['subscriberId'] = $subscriber->getSubscriberId();
        $result['subscriberStatus'] = $subscriber->getSubscriberStatus() === '1' ? '1' : '0';
        $result['unsubscriptionURL'] = $subscriber->getUnsubscriptionLink();
        $result['modifiedDate'] = $this->dateTime->date(self::UPDATED_AT_DATE_FORMAT, $subscriber->getChangeStatusAt());
        $this->getWriter()->writeRow($result);
        $this->_processedRowsCount++;
    }

    /**
     * Get modified customer id
     *
     * @param int|string $customerId
     * @return mixed|string
     */
    public function getModifiedCustomerId($customerId)
    {
        if ($customerId === '0') {
            return $customerId;
        }

        $prefix = $this->scopeConfig->getValue('aep/general/customer_id_prefix');

        if ($prefix !== null) {
            $customerId = $prefix . $customerId;
        }

        return $customerId;
    }
}
