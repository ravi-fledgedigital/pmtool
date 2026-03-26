<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Model;

use Magento\Framework\Validator\ValidatorInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Validator\EmailAddress;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaAttributesManagementInterface;
use Magento\Rma\Model\Item\Attribute\Source\Status;
use Magento\Rma\Model\Rma\EntityAttributesLoader;
use Magento\Sales\Model\Order\Address;
use Magento\Store\Model\Store;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Rma extends \Magento\Sales\Model\AbstractModel implements \Magento\Rma\Api\Data\RmaInterface
{
    /**#@+
     * Constants defined for keys of array
     */
    public const ENTITY_ID = 'entity_id';

    public const ORDER_ID = 'order_id';

    public const ORDER_INCREMENT_ID = 'order_increment_id';

    public const INCREMENT_ID = 'increment_id';

    public const STORE_ID = 'store_id';

    public const CUSTOMER_ID = 'customer_id';

    public const DATE_REQUESTED = 'date_requested';

    public const CUSTOMER_CUSTOM_EMAIL = 'customer_custom_email';

    public const ITEMS = 'items';

    public const STATUS = 'status';

    public const COMMENTS = 'comments';

    public const TRACKS = 'tracks';

    public const QTY_AUTHORIZED = 'qty_authorized';

    public const QTY_RETURNED = 'qty_returned';

    public const QTY_APPROVED = 'qty_approved';

    public const QTY_REQUESTED = 'qty_requested';

    /**#@-*/

    /**
     * XML configuration paths
     */
    public const XML_PATH_SECTION_RMA = 'sales/magento_rma/';

    public const XML_PATH_ENABLED = 'sales/magento_rma/enabled';

    public const XML_PATH_USE_STORE_ADDRESS = 'sales/magento_rma/use_store_address';

    /**
     * Rma order object
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * Rma shipping collection
     *
     * @var \Magento\Rma\Model\ResourceModel\Shipping\Collection
     */
    protected $_trackingNumbers;

    /**
     * Rma shipping model
     *
     * @var \Magento\Rma\Model\Shipping
     */
    protected $_shippingLabel;

    /**
     * Rma helper data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * Core session model
     *
     * @var \Magento\Framework\Session\Generic
     */
    protected $_session;

    /**
     * Core store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Rma item factory model
     *
     * @var \Magento\Rma\Model\ItemFactory
     */
    protected $_rmaItemFactory;

    /**
     * Rma item attribute status factory
     *
     * @var \Magento\Rma\Model\Item\Attribute\Source\StatusFactory
     */
    protected $_attrSourceFactory;

    /**
     * Rma source status factory
     *
     * @var \Magento\Rma\Model\Rma\Source\StatusFactory
     */
    protected $_statusFactory;

    /**
     * Rma item factory
     *
     * @var \Magento\Rma\Model\ResourceModel\ItemFactory
     */
    protected $_itemFactory;

    /**
     * Rma item collection factory
     *
     * @var \Magento\Rma\Model\ResourceModel\Item\CollectionFactory
     */
    protected $_itemsFactory;

    /**
     * Rma shipping collection factory
     *
     * @var \Magento\Rma\Model\ResourceModel\Shipping\CollectionFactory
     */
    protected $_rmaShippingFactory;

    /**
     * Sales quote factory
     *
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * Sales quote address rate factory
     *
     * @var \Magento\Quote\Model\Quote\Address\RateFactory
     */
    protected $_quoteRateFactory;

    /**
     * Sales quote item factory
     *
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $_quoteItemFactory;

    /**
     * Sales order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * Sales order item collection factory
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $_ordersFactory;

    /**
     * Sales quote address rate request factory
     *
     * @var \Magento\Quote\Model\Quote\Address\RateRequestFactory
     */
    protected $_rateRequestFactory;

    /**
     * Shipping factory model
     *
     * @var \Magento\Shipping\Model\ShippingFactory
     */
    protected $_shippingFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Message manager
     *
     * @var \Magento\Rma\Api\RmaAttributesManagementInterface
     */
    protected $metadataService;

    /**
     * Serializer instance.
     *
     * @var Json
     */
    private $serializer;

    /**
     * @var EntityAttributesLoader
     */
    private $attributesLoader;

    /**
     * Model event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'rma';

    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'rma';

    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;

    /**
     * @var array
     */
    private array $errorKeys = [];

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Framework\Session\Generic $session
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ItemFactory $rmaItemFactory
     * @param Item\Attribute\Source\StatusFactory $attrSourceFactory
     * @param Rma\Source\StatusFactory $statusFactory
     * @param \Magento\Rma\Model\ResourceModel\ItemFactory $itemFactory
     * @param \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $itemsFactory
     * @param \Magento\Rma\Model\ResourceModel\Shipping\CollectionFactory $rmaShippingFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\Quote\Address\RateFactory $quoteRateFactory
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $ordersFactory
     * @param \Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory
     * @param \Magento\Shipping\Model\ShippingFactory $shippingFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param RmaAttributesManagementInterface $metadataService
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param Json|null $serializer
     * @param EntityAttributesLoader|null $attributesLoader
     * @param ValidatorInterface|null $validator
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Framework\Session\Generic $session,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Rma\Model\ItemFactory $rmaItemFactory,
        \Magento\Rma\Model\Item\Attribute\Source\StatusFactory $attrSourceFactory,
        \Magento\Rma\Model\Rma\Source\StatusFactory $statusFactory,
        \Magento\Rma\Model\ResourceModel\ItemFactory $itemFactory,
        \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $itemsFactory,
        \Magento\Rma\Model\ResourceModel\Shipping\CollectionFactory $rmaShippingFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\Quote\Address\RateFactory $quoteRateFactory,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $ordersFactory,
        \Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory,
        \Magento\Shipping\Model\ShippingFactory $shippingFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        RmaAttributesManagementInterface $metadataService,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null,
        EntityAttributesLoader $attributesLoader = null,
        ?ValidatorInterface $validator = null
    ) {
        $objectManager = ObjectManager::getInstance();
        $this->_rmaData = $rmaData;
        $this->_session = $session;
        $this->_storeManager = $storeManager;
        $this->_rmaItemFactory = $rmaItemFactory;
        $this->_attrSourceFactory = $attrSourceFactory;
        $this->_statusFactory = $statusFactory;
        $this->_itemFactory = $itemFactory;
        $this->_itemsFactory = $itemsFactory;
        $this->_rmaShippingFactory = $rmaShippingFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteRateFactory = $quoteRateFactory;
        $this->_quoteItemFactory = $quoteItemFactory;
        $this->_orderFactory = $orderFactory;
        $this->_ordersFactory = $ordersFactory;
        $this->_rateRequestFactory = $rateRequestFactory;
        $this->_shippingFactory = $shippingFactory;
        $this->_localeDate = $localeDate;
        $this->messageManager = $messageManager;
        $this->metadataService = $metadataService;
        $this->serializer = $serializer ?: $objectManager->get(Json::class);
        $this->attributesLoader = $attributesLoader ?: $objectManager->get(EntityAttributesLoader::class);
        $this->validator = $validator ?: $objectManager->get(ValidatorInterface::class);
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    protected function getCustomAttributesCodes()
    {
        if ($this->customAttributesCodes === null) {
            $this->customAttributesCodes = $this->getEavAttributesCodes($this->metadataService);
        }
        return $this->customAttributesCodes;
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(\Magento\Rma\Api\Data\RmaExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get increment id
     *
     * @codeCoverageIgnoreStart
     * @return mixed|string
     */
    public function getIncrementId()
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setIncrementId($incrementId)
    {
        return $this->setData(self::INCREMENT_ID, $incrementId);
    }

    /**
     * Get entity id
     *
     * @return int|mixed
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get order id
     *
     * @return int|mixed
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderIncrementId()
    {
        return $this->getData(self::ORDER_INCREMENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderIncrementId($incrementId)
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $incrementId);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function getDateRequested()
    {
        return $this->getData(self::DATE_REQUESTED);
    }

    /**
     * @inheritdoc
     */
    public function setDateRequested($dateRequested)
    {
        return $this->setData(self::DATE_REQUESTED, $dateRequested);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerCustomEmail()
    {
        return $this->getData(self::CUSTOMER_CUSTOM_EMAIL);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerCustomEmail($customerCustomEmail)
    {
        return $this->setData(self::CUSTOMER_CUSTOM_EMAIL, $customerCustomEmail);
    }

    /**
     * Get items
     *
     * @return array|\Magento\Rma\Api\Data\ItemInterface[]|mixed
     */
    public function getItems()
    {
        $items = $this->getData(self::ITEMS);
        if ($items === null) {
            $items = $this->attributesLoader->getItems((int)$this->getEntityId());
            $this->setItems($items);
        }

        if (!empty($items) && $this->getEntityId()) {
            /** @var RmaInterface $item */
            foreach ($items as $item) {
                if (!$item->getRmaEntityId()) {
                    $item->setRmaEntityId($this->getEntityId());
                }
            }
            $this->setItems($items);
        }

        return $items;
    }

    /**
     * Set items
     *
     * @param array|null $items
     * @return $this|RmaInterface
     */
    public function setItems(array $items = null)
    {
        return $this->setData(self::ITEMS, $items);
    }

    /**
     * Get status
     *
     * @return mixed|string
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get comments
     *
     * @return array|\Magento\Rma\Api\Data\CommentInterface[]|mixed
     */
    public function getComments()
    {
        $comments = $this->getData(self::COMMENTS);
        if ($comments === null) {
            $comments = $this->attributesLoader->getComments((int)$this->getEntityId());
            $this->setComments($comments);
        }

        return $comments;
    }

    /**
     * Set comments
     *
     * @param array|null $comments
     * @return $this|RmaInterface
     */
    public function setComments(array $comments = null)
    {
        return $this->setData(self::COMMENTS, $comments);
    }

    /**
     * @inheritdoc
     */
    public function getTracks()
    {
        $tracks = $this->getData(self::TRACKS);
        if ($tracks === null) {
            $tracks = $this->attributesLoader->getTracks((int)$this->getEntityId(), false);
            $this->setTracks($tracks);
        }

        return $tracks;
    }

    /**
     * Set tracks
     *
     * @param array|null $tracks
     * @return $this|RmaInterface
     */
    public function setTracks(array $tracks = null)
    {
        return $this->setData(self::TRACKS, $tracks);
    }

    //@codeCoverageIgnoreEnd

    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Rma\Model\ResourceModel\Rma::class);
        parent::_construct();
    }

    /**
     * Get available statuses for RMAs
     *
     * @return array
     */
    public function getAllStatuses()
    {
        /** @var $sourceStatus \Magento\Rma\Model\Rma\Source\Status */
        $sourceStatus = $this->_statusFactory->create();
        return $sourceStatus->getAllOptionsForGrid();
    }

    /**
     * Get RMA's status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        if (parent::getStatusLabel() === null) {
            /** @var $sourceStatus \Magento\Rma\Model\Rma\Source\Status */
            $sourceStatus = $this->_statusFactory->create();
            $this->setStatusLabel($sourceStatus->getItemLabel($this->getStatus()));
        }
        return parent::getStatusLabel();
    }

    /**
     * Get rma order object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->_orderFactory->create()->load($this->getOrderId());
        }
        return $this->_order;
    }

    /**
     * Retrieves rma close availability
     *
     * @return bool
     */
    public function canClose()
    {
        $status = $this->getStatus();
        if ($status === \Magento\Rma\Model\Rma\Source\Status::STATE_CLOSED
            || $status === \Magento\Rma\Model\Rma\Source\Status::STATE_PROCESSED_CLOSED
        ) {
            return false;
        }

        return true;
    }

    /**
     * Close rma
     *
     * @return \Magento\Rma\Model\Rma
     */
    public function close()
    {
        if ($this->canClose()) {
            $this->setStatus(\Magento\Rma\Model\Rma\Source\Status::STATE_CLOSED);
        }
        return $this;
    }

    /**
     * Save Rma
     *
     * @param array $data
     * @return bool|$this
     * @throws \Exception
     */
    public function saveRma($data)
    {
        // TODO: move errors adding to controller
        $this->messageManager->getMessages(true);
        if ($this->getCustomerCustomEmail()) {
            $validateEmail = $this->_validateEmail($this->getCustomerCustomEmail());
            if (is_array($validateEmail)) {
                foreach ($validateEmail as $error) {
                    $this->messageManager->addError($error);
                }
                $this->_session->setRmaFormData($data);
            }
        }

        try {
            $this->_createItemsCollection($data);
        } catch (\Throwable $e) {
            return false;
        }

        return $this->save();
    }

    /**
     * Prepares Item's data
     *
     * @param array $item
     * @return array
     */
    protected function _preparePost($item)
    {
        $preparePost = [];
        ksort($item);

        foreach ($item as $key => $value) {
            switch ($key) {
                case 'order_item_id':
                    $preparePost['order_item_id'] = (int)$value;
                    break;
                case 'qty_requested':
                    $preparePost['qty_requested'] = is_numeric($value) ? $value : 0;
                    break;
                case 'resolution':
                    $preparePost['resolution'] = (int)$value;
                    break;
                case 'condition':
                    $preparePost['condition'] = (int)$value;
                    break;
                case 'reason':
                    $preparePost['reason'] = (int)$value;
                    break;
                case 'reason_other':
                    $preparePost['reason_other'] = $value;
                    break;
                default:
                    $preparePost[$key] = $value;
            }
        }

        $preparePost = $this->preparePostItemStatus($preparePost);
        $preparePost = $this->preparePostItemProductDetails($preparePost);

        return $this->preparePostItemQuantities($preparePost);
    }

    /**
     * Creates rma items collection by passed data
     *
     * @param mixed $data
     * @return Item[]
     * @throws \Exception
     */
    protected function _createItemsCollection($data): array
    {
        if (!is_array($data)) {
            $data = (array)$data;
        }

        $itemModels = [];
        foreach ($data['items'] as $key => $item) {
            $itemModels = $this->mergeModels($itemModels, $this->createItemModel($item, (string)$key));
        }
        $this->setItems($itemModels);
        $result = $this->validator->isValid($this);

        if ($result !== true) {
            $errors = $this->validator->getMessages();
            $this->errorKeys = array_merge($this->errorKeys, $errors['error_keys'] ?: []);
            unset($errors['error_keys']);
            $this->addErrors($errors);
        }

        if (!empty($this->messageManager->getMessages()->getErrors())) {
            $this->_session->setRmaFormData($data);
            if (!empty($this->errorKeys)) {
                $this->_session->setRmaErrorKeys($this->errorKeys);
            }
        }

        if (empty($itemModels) || !empty($this->messageManager->getMessages()->getErrors())) {
            throw new \LogicException('Faulty RMA items');
        }

        return $this->getItems();
    }

    /**
     * Validate email
     *
     * @param string $value
     * @return string
     */
    protected function _validateEmail($value)
    {
        $label = $this->_rmaData->getContactEmailLabel();

        $validator = new EmailAddress();
        $validator->setMessage(__('You entered an invalid type: "%1".', $label), EmailAddress::INVALID);
        $validator->setMessage(
            __('You entered an invalid email address: "%1".', $label),
            EmailAddress::INVALID_FORMAT
        );
        $validator->setMessage(
            __('You entered an invalid hostname: "%1"', $label),
            EmailAddress::INVALID_HOSTNAME
        );
        $validator->setMessage(
            __('You entered an invalid hostname: "%1"', $label),
            EmailAddress::INVALID_MX_RECORD
        );
        $validator->setMessage(
            __('You entered an invalid hostname: "%1"', $label),
            EmailAddress::INVALID_MX_RECORD
        );
        $validator->setMessage(
            __('You entered an invalid email address: "%1".', $label),
            EmailAddress::DOT_ATOM
        );
        $validator->setMessage(
            __('You entered an invalid email address: "%1".', $label),
            EmailAddress::QUOTED_STRING
        );
        $validator->setMessage(
            __('You entered an invalid email address: "%1".', $label),
            EmailAddress::INVALID_LOCAL_PART
        );
        $validator->setMessage(
            __('"%1" is longer than allowed.', $label),
            EmailAddress::LENGTH_EXCEEDED
        );
        if (!$validator->isValid($value)) {
            return array_unique($validator->getMessages());
        }

        return true;
    }

    /**
     * Get formated RMA created date in store timezone
     *
     * @param   string $format date format type (short|medium|long|full)
     * @return  string
     */
    public function getCreatedAtFormated($format)
    {
        $storeTimezone = $this->_localeDate->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore($this->getStoreId())->getCode()
        );
        $requestedDate = new \DateTime($this->getDateRequested());
        $scopeDate = $this->_localeDate->formatDateTime(
            $requestedDate,
            $format,
            $format,
            null,
            $storeTimezone
        );
        return $scopeDate;
    }

    /**
     * Gets Shipping Methods
     *
     * @param bool $returnItems Flag if needs to return Items
     * @return array|bool
     */
    public function getShippingMethods($returnItems = false)
    {
        $found = false;
        $address = false;
        /** @var $itemResource \Magento\Rma\Model\ResourceModel\Item */
        $itemResource = $this->_itemFactory->create();
        $rmaItems = $itemResource->getAuthorizedItems($this->getId());

        if (!empty($rmaItems)) {
            /** @var $quoteItemsCollection \Magento\Sales\Model\ResourceModel\Order\Item\Collection */
            $quoteItemsCollection = $this->_ordersFactory->create();
            $quoteItemsCollection->addFieldToFilter('item_id', ['in' => array_keys($rmaItems)])->getData();

            $quoteItems = [];
            $subtotal = $weight = $qty = $storeId = 0;
            foreach ($quoteItemsCollection as $item) {
                /** @var $itemModel \Magento\Quote\Model\Quote\Item */
                $itemModel = $this->_quoteItemFactory->create();

                $item['qty'] = $rmaItems[$item['item_id']]['qty'];
                $item['name'] = $rmaItems[$item['item_id']]['product_name'];
                $item['row_total'] = $item['price'] * $item['qty'];
                $item['base_row_total'] = $item['base_price'] * $item['qty'];
                $item['row_total_with_discount'] = 0;
                $item['row_weight'] = $item['weight'] * $item['qty'];
                $item['price_incl_tax'] = $item['price'];
                $item['base_price_incl_tax'] = $item['base_price'];
                $item['row_total_incl_tax'] = $item['row_total'];
                $item['base_row_total_incl_tax'] = $item['base_row_total'];

                $quoteItems[] = $itemModel->addData($item->toArray());

                $subtotal += $item['base_row_total'];
                $weight += $item['row_weight'];
                $qty += $item['qty'];

                if (!$storeId) {
                    $storeId = $item['store_id'];
                    /** @var $order \Magento\Sales\Model\Order */
                    $order = $this->_orderFactory->create()->load($item['order_id']);
                    /** @var $address Address */
                    $address = $order->getShippingAddress();
                }
                /** @var $quote \Magento\Quote\Model\Quote */
                $quote = $this->_quoteFactory->create();
                $quote->setStoreId($storeId);
                $itemModel->setQuote($quote);
            }

            if ($returnItems) {
                return $quoteItems;
            }

            $store = $this->_storeManager->getStore($storeId);
            $this->setStore($store);

            $found = $this->_requestShippingRates($quoteItems, $address, $store, $subtotal, $weight, $qty);
        }

        return $found;
    }

    /**
     * Returns Shipping Rates
     *
     * @param array $items
     * @param Address|bool $address Shop address
     * @param Store $store
     * @param int $subtotal
     * @param int $weight
     * @param int $qty
     *
     * @return array|false
     */
    protected function _requestShippingRates($items, $address, $store, $subtotal, $weight, $qty)
    {
        /** @var \Magento\Quote\Model\Quote\Address $shippingDestinationInfo */
        $shippingDestinationInfo = $this->_rmaData->getReturnAddressModel($this->getStoreId());

        /** @var $request \Magento\Quote\Model\Quote\Address\RateRequest */
        $request = $this->_rateRequestFactory->create();
        $request->setAllItems($items);
        $request->setDestCountryId($shippingDestinationInfo->getCountryId());
        $request->setDestRegionId($shippingDestinationInfo->getRegionId());
        $request->setDestRegionCode($shippingDestinationInfo->getRegionId());
        $request->setDestStreet($shippingDestinationInfo->getStreetFull());
        $request->setDestCity($shippingDestinationInfo->getCity());
        $request->setDestPostcode($shippingDestinationInfo->getPostcode());
        $request->setDestCompanyName($shippingDestinationInfo->getCompany());

        $request->setPackageValue($subtotal);
        $request->setPackageValueWithDiscount($subtotal);
        $request->setPackageWeight($weight);
        $request->setPackageQty($qty);

        //shop destination address data
        //different carriers use different variables. So we duplicate them
        $request->setOrigCountryId(
            $address->getCountryId()
        )->setOrigCountry(
            $address->getCountryId()
        )->setOrigState(
            $address->getRegionId()
        )->setOrigRegionCode(
            $address->getRegionId()
        )->setOrigCity(
            $address->getCity()
        )->setOrigPostcode(
            $address->getPostcode()
        )->setOrigPostal(
            $address->getPostcode()
        )->setOrigCompanyName(
            $address->getCompany() ? $address->getCompany() : 'NA'
        )->setOrig(
            true
        );

        /**
         * Need for shipping methods that use insurance based on price of physical products
         */
        $request->setPackagePhysicalValue($subtotal);

        $request->setFreeMethodWeight(0);

        /**
         * Store and website identifiers need specify from quote
         */
        $request->setStoreId($store->getId());
        $request->setWebsiteId($store->getWebsiteId());
        /**
         * Currencies need to convert in free shipping
         */
        $request->setBaseCurrency($store->getBaseCurrency());
        $request->setPackageCurrency($store->getCurrentCurrency());

        /*
         * For international shipments we must set customs value larger than zero
         * This number is being taken from items' prices
         * But for the case when we try to return bundle items from fixed-price bundle,
         * we have no items' prices. We should add this customs value manually
         */
        if ($request->getOrigCountryId() !== $request->getDestCountryId() && $request->getPackageValue() < 1) {
            $request->setPackageCustomsValue(1);
        }

        $request->setIsReturn(true);

        /** @var $shipping \Magento\Shipping\Model\Shipping */
        $shipping = $this->_shippingFactory->create();
        $result = $shipping->setCarrierAvailabilityConfigField('active_rma')->collectRates($request)->getResult();

        $found = [];
        if ($result) {
            $shippingRates = $result->getAllRates();

            foreach ($shippingRates as $shippingRate) {
                if (in_array($shippingRate->getCarrier(), array_keys($this->_rmaData->getShippingCarriers()))) {
                    /** @var $addressRate \Magento\Quote\Model\Quote\Address\Rate */
                    $addressRate = $this->_quoteRateFactory->create();
                    $found[] = $addressRate->importShippingRate($shippingRate);
                }
            }
        }
        return !empty($found) ? $found : false;
    }

    /**
     * Get collection of tracking on this RMA
     *
     * @return \Magento\Rma\Model\ResourceModel\Shipping\Collection
     */
    public function getTrackingNumbers()
    {
        if ($this->_trackingNumbers === null) {
            $this->_trackingNumbers = $this->_rmaShippingFactory->create();
            $this->_trackingNumbers->addFieldToFilter('rma_entity_id', $this->getEntityId());
            $this->_trackingNumbers->addFieldToFilter(
                'is_admin',
                ['neq' => \Magento\Rma\Model\Shipping::IS_ADMIN_STATUS_ADMIN_LABEL]
            );
        }
        return $this->_trackingNumbers;
    }

    /**
     * Get shipping label RMA
     *
     * @return \Magento\Rma\Model\Shipping
     */
    public function getShippingLabel()
    {
        if ($this->_shippingLabel === null) {
            /** @var $shippingCollection \Magento\Rma\Model\ResourceModel\Shipping\Collection */
            $shippingCollection = $this->_rmaShippingFactory->create();
            $this->_shippingLabel = $shippingCollection->addFieldToFilter(
                'rma_entity_id',
                $this->getEntityId()
            )->addFieldToFilter(
                'is_admin',
                \Magento\Rma\Model\Shipping::IS_ADMIN_STATUS_ADMIN_LABEL
            )->getFirstItem();
        }
        return $this->_shippingLabel;
    }

    /**
     * Defines whether RMA status and RMA Items statuses allow to create shipping label
     *
     * @return bool
     */
    public function isAvailableForPrintLabel()
    {
        return (bool)($this->_isRmaAvailableForPrintLabel() && $this->_isItemsAvailableForPrintLabel());
    }

    /**
     * Defines whether RMA status allow to create shipping label
     *
     * @return bool
     */
    protected function _isRmaAvailableForPrintLabel()
    {
        return $this->getStatus() !== \Magento\Rma\Model\Rma\Source\Status::STATE_CLOSED
            && $this->getStatus() !== \Magento\Rma\Model\Rma\Source\Status::STATE_PROCESSED_CLOSED
            && $this->getStatus() !== \Magento\Rma\Model\Rma\Source\Status::STATE_PENDING;
    }

    /**
     * Defines whether RMA items' statuses allow to create shipping label
     *
     * @return bool
     */
    protected function _isItemsAvailableForPrintLabel()
    {
        /** @var $collection \Magento\Rma\Model\ResourceModel\Item\Collection */
        $collection = $this->_itemsFactory->create();
        $collection->addFieldToFilter('rma_entity_id', $this->getEntityId());

        $return = false;
        foreach ($collection as $item) {
            if (!in_array(
                $item->getStatus(),
                [
                    Status::STATE_AUTHORIZED,
                    Status::STATE_DENIED,
                ],
                true
            )
            ) {
                return false;
            }
            if ($item->getStatus() === Status::STATE_AUTHORIZED
                && is_numeric(
                    $item->getQtyAuthorized()
                )
                && $item->getQtyAuthorized() > 0
            ) {
                $return = true;
            }
        }
        return $return;
    }

    /**
     * Get collection of RMA Items with common order rules to be displayed in different lists
     *
     * @param bool $withoutAttributes - sets whether add EAV attributes into select
     * @return \Magento\Rma\Model\ResourceModel\Item\Collection
     */
    public function getItemsForDisplay($withoutAttributes = false)
    {
        /** @var $collection \Magento\Rma\Model\ResourceModel\Item\Collection */
        $collection = $this->_itemsFactory->create();
        $collection->addFieldToFilter(
            'rma_entity_id',
            $this->getEntityId()
        )->setOrder(
            'order_item_id'
        )->setOrder(
            'entity_id'
        );

        if (!$withoutAttributes) {
            $collection->addAttributeToSelect('*');
        }
        return $collection;
    }

    /**
     * Get button disabled status
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getButtonDisabledStatus()
    {
        /** @var $sourceStatus \Magento\Rma\Model\Rma\Source\Status */
        $sourceStatus = $this->_statusFactory->create();
        return $sourceStatus->getButtonDisabledStatus($this->getStatus()) && $this->_isItemsNotInPendingStatus();
    }

    /**
     * Defines whether RMA items' not in pending status
     *
     * @return bool
     */
    public function _isItemsNotInPendingStatus()
    {
        /** @var $collection \Magento\Rma\Model\ResourceModel\Item\Collection */
        $collection = $this->_itemsFactory->create();
        $collection->addFieldToFilter('rma_entity_id', $this->getEntityId());

        foreach ($collection as $item) {
            if ($item->getStatus() == Status::STATE_PENDING) {
                return false;
            }
        }
        return true;
    }

    /**
     * Workaround method to check which status needs confirmation email to the customer
     *
     * By design only \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_AUTHORIZED has such email
     * but other statuses also need it
     *
     * @param string $status
     * @return bool
     */
    public function isStatusNeedsAuthEmail($status): bool
    {
        $statusesNeedsEmail = [
            Status::STATE_AUTHORIZED,
            Status::STATE_RECEIVED,
            Status::STATE_APPROVED,
            Status::STATE_REJECTED,
            Status::STATE_DENIED
        ];

        return in_array($status, $statusesNeedsEmail);
    }

    /**
     * Validate order items.
     *
     * @return void
     * @throws LocalizedException
     */
    public function validateOrderItems(): void
    {
        /** @var $order \Magento\Sales\Model\Order */
        $order = $this->getOrder();
        $items = $order->getItems();
        foreach ($items as $item) {
            if (!$item->getProduct()) {
                throw new LocalizedException(
                    __(
                        'The label cannot be created for \'%1\' because the product does not exist in the system.',
                        $item->getName()
                    )
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function _getValidationRulesBeforeSave()
    {
        return $this->validator;
    }

    /**
     * Adds items to existing list
     *
     * @param array $itemModels
     * @param array $newModels
     * @return array
     */
    private function mergeModels(array $itemModels, array $newModels): array
    {
        return array_merge($itemModels, $newModels);
    }

    /**
     * Set item quantities to proper type
     *
     * @param array $itemDetails
     * @return array
     */
    private function preparePostItemQuantities(array $itemDetails): array
    {
        $quantityKeys = [self::QTY_AUTHORIZED, self::QTY_APPROVED, self::QTY_RETURNED];

        foreach ($itemDetails as $key => $val) {
            if (in_array($key, $quantityKeys)) {
                if (is_numeric($val)) {
                    $preparePost[$key] = (double)$val;
                } else {
                    $preparePost[$key] = '';
                }
            }
        }

        if ($itemDetails['is_qty_decimal']) {
            $itemDetails['qty_requested'] = (double)$itemDetails['qty_requested'];
        } else {
            $itemDetails['qty_requested'] = (int)$itemDetails['qty_requested'];

            foreach ($quantityKeys as $key) {
                if (!empty($preparePost[$key])) {
                    $preparePost[$key] = (int)$preparePost[$key];
                }
            }
        }

        return $itemDetails;
    }

    /**
     * Adds real product information to item details
     *
     * @param array $itemDetails
     * @return array
     */
    private function preparePostItemProductDetails(array $itemDetails): array
    {
        $realItem = $this->getOrder()->getItemById($itemDetails['order_item_id']);

        $itemDetails['product_name'] = $realItem->getName();
        $itemDetails['product_sku'] = $realItem->getSku();
        $itemDetails['product_admin_name'] = $this->_rmaData->getAdminProductName($realItem);
        $itemDetails['product_admin_sku'] = $this->_rmaData->getAdminProductSku($realItem);
        $itemDetails['product_options'] = $this->serializer->serialize($realItem->getProductOptions());
        $itemDetails['is_qty_decimal'] = $realItem->getIsQtyDecimal();

        return $itemDetails;
    }

    /**
     * Add messages to existing message hub
     *
     * @param array $errors
     */
    private function addErrors(array $errors): void
    {
        foreach ($errors as $message) {
            $this->messageManager->addError($message);
        }
    }

    /**
     * Normalize item post status
     *
     * @param array $itemDetails
     * @return array
     */
    private function preparePostItemStatus(array $itemDetails): array
    {
        $stat = Status::STATE_PENDING;
        if (!empty($itemDetails['status'])) {
            $status = $this->_attrSourceFactory->create();
            if ($status->checkStatus($itemDetails['status'])) {
                $stat = $itemDetails['status'];
            }
        }

        $itemDetails['status'] = $stat;

        return $itemDetails;
    }

    /**
     * Generate model with provided item details
     *
     * @param array $item
     * @param string $key
     * @return array
     */
    private function createItemModel(array $item, string $key): array
    {
        if (isset($item['items'])) {
            $itemModel = $this->createSubItems($item['items'], $key);
        } else {
            $itemModel = $this->hydrateItemEntity($item, $key);
            //check if entity_id present, and it's a split return. Reset the entity
            if ($itemModel->getEntityId() &&
                !is_numeric($itemModel->getEntityId())) {
                $itemModel->setEntityId(null);
            }

            if ($this->isStatusNeedsAuthEmail($itemModel->getStatus())
                && $itemModel->getOrigData('status') !== $itemModel->getStatus()
            ) {
                $this->setIsSendAuthEmail(1);
            }

            $this->addErrors($itemModel->getErrors());
            if ($itemModel->getErrors()) {
                $this->errorKeys['tabs'] = 'items_section';
            }
            $itemModel = [$itemModel];
        }

        return $itemModel;
    }

    /**
     * Adds data to existing entity, if available
     *
     * @param array $itemDetails
     * @param string $key
     * @return Item
     */
    private function hydrateItemEntity(array $itemDetails, string $key): Item
    {
        $itemModel = $this->_rmaItemFactory->create();
        if (isset($itemDetails['entity_id']) && $itemDetails['entity_id']) {
            $itemModel->load($itemDetails['entity_id']);
            if ($itemModel->getEntityId()) {
                if (empty($itemDetails['reason'])) {
                    $itemDetails['reason'] = $itemModel->getReason();
                }

                if (empty($itemDetails['reason_other'])) {
                    $itemDetails['reason_other'] =
                        $itemModel->getReasonOther() === null ? '' : $itemModel->getReasonOther();
                }

                if (empty($itemDetails['condition'])) {
                    $itemDetails['condition'] = $itemModel->getCondition();
                }

                if (empty($itemDetails['qty_requested'])) {
                    $itemDetails['qty_requested'] = $itemModel->getQtyRequested();
                }
            }
        }

        $itemPost = $this->_preparePost($itemDetails);
        $itemModel->setData($itemPost)->prepareAttributes($itemPost, $key);

        return $itemModel;
    }

    /**
     * Creates sub-items for returned product
     *
     * @param array $item
     * @param string $key
     * @return array
     */
    private function createSubItems(array $item, string $key): ?array
    {
        if (empty($item['items'])) {
            return [];
        }

        $items = [];
        foreach ($item['items'] as $id => $qty) {
            $itemModel = $this->_rmaItemFactory->create();
            $subItem = $item;
            unset($subItem['items']);
            $subItem['order_item_id'] = $id;
            $subItem['qty_requested'] = $qty;

            $itemPost = $this->_preparePost($subItem);

            $files = $itemModel->setData($itemPost)->prepareAttributes($itemPost, $key);
            if (!empty($files)) {
                foreach ($files as $code) {
                    $itemModel->setData($code, $itemModel->getData($code));
                }
            }
            $this->addErrors($itemModel->getErrors());
            $items[] = $itemModel;
        }

        return $items;
    }
}
