<?php

namespace Seoulwebdesign\Toast\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class Message extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'seoulwebdesign_toast_message';
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;

    public const ORDER_CANCELED = 'order_canceled';
    public const ORDER_REFUNDED = 'order_refunded';
    public const ORDER_PLACED = 1;
    public const ORDER_INVOICED = 2;
    public const ORDER_DELIVERING = 3;
    public const ORDER_COMPLETED = 4;
    public const ORDER_REFUND_REQUEST = 'refund_request';
    public const ORDER_CANCEL_REQUEST = 'cancel_request';
    public const CUSTOMER_REGISTERED = 10;
    public const PAYMENT_CAPTURED = 20;

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;
    /**
     * @var string
     */
    protected $_eventPrefix = self::CACHE_TAG;
    /**
     * @var CollectionFactory
     */
    protected $statusCollectionFactory;
    /**
     * @var array
     */
    private $availableSendActions;

    /**
     * The contructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory $statusCollectionFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $availableSendActions
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $statusCollectionFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $availableSendActions = [],
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection);
        $this->availableSendActions = $availableSendActions;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->_init(\Seoulwebdesign\Toast\Model\ResourceModel\Message::class);
    }

    /**
     * Get Identities
     *
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get Default Values
     *
     * @return array
     */
    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }

    /**
     * Get Available Statuses
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_ENABLED => __('Enabled'),
            self::STATUS_DISABLED => __('Disabled')
        ];
    }

    /**
     * Get Available Send Actions
     *
     * @return array
     */
    public function getAvailableSendActions()
    {
        $result = [];
        foreach ($this->availableSendActions as $key => $val) {
            $result[$key] = __($val);
        }
        $orderStatus = $this->getOrderStatus();
        $result = array_merge($result, $orderStatus);
        $result[self::CUSTOMER_REGISTERED] = __('Customer Registered');
        return $result;
    }

    /**
     * Get Order Status
     *
     * @return array
     */
    protected function getOrderStatus()
    {
        $result = [
            self::ORDER_CANCEL_REQUEST =>__('Order cancel requested'),
            self::ORDER_CANCELED =>__('Order cancelled'),
            self::ORDER_REFUND_REQUEST =>__('Order refund requested'),
            self::ORDER_REFUNDED =>__('Order refunded'),

        ];
        $options = $this->statusCollectionFactory->create()->toOptionArray();
        foreach ($options as $option) {
            $result[$option['value']] =  __('Order status: ' . $option['label']);
        }
        return $result;
    }

    /**
     * Get Order Status Key
     *
     * @return array
     */
    public function getOrderStatusKey()
    {
        $re = [];
        $statuses = $this->getOrderStatus();
        foreach ($statuses as $code => $label) {
            $re[]=$code;
        }
        return $re;
    }

    /**
     * Set Status
     *
     * @param string $status
     * @return Message
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }
}
