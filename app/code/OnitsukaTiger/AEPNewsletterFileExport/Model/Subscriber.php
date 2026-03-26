<?php

namespace OnitsukaTiger\AEPNewsletterFileExport\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use OnitsukaTiger\AEPNewsletterFileExport\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Subscriber backup model
 */
class Subscriber extends AbstractModel
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'newsletter_subscriber_backup';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getObject() in this case
     *
     * @var string
     */
    protected $_eventObject = 'subscriber_backup';

    /**
     * @var Data
     */
    private Data $newsletterData;

    /**
     * @var array
     */
    private $storeCodeCache = [];

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param Data $newsletterData
     * @param StoreManagerInterface $storeManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Data $newsletterData,
        StoreManagerInterface $storeManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->newsletterData = $newsletterData;
        $this->storeManager = $storeManager;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Subscriber::class);
    }

    /**
     * Returns Unsubscribe url
     *
     * @return string
     */
    public function getUnsubscriptionLink()
    {
        return $this->newsletterData->getUnsubscribeUrl($this);
    }

    /**
     * Get store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        $storeId = $this->getStoreId();
        if (isset($this->storeCodeCache[$storeId])) {
            return $this->storeCodeCache[$storeId];
        }

        $store = $this->storeManager->getStore($storeId);
        $storeCode = $store->getCode();

        $this->storeCodeCache[$storeId] = $storeCode;

        return $storeCode;
    }

    /**
     * Get subscriber confirm code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getSubscriberConfirmCode();
    }
}
