<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request\Email;

use Amasty\Rma\Controller\RegistryConstants;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Status\Repository;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\UrlCoder;
use Magento\Framework\Url;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;

class EmailRequest extends DataObject
{
    public const ORDER_INCREMENT = 'order_increment';
    public const ORDER_CREATED_AT = 'order_created_at';
    public const ORDER = 'order';
    public const STATUS = 'status';
    public const STATUS_COLOR = 'status_color';
    public const REQUEST_ID = 'request_id';
    public const CUSTOMER_NAME = 'customer_name';
    public const CUSTOMER_EMAIL = 'customer_email';
    public const CUSTOM_FIELDS = 'custom_fields';
    public const REQUEST_ITEMS = 'request_items';
    public const URL = 'url';

    /**
     * @var Repository
     */
    private $statusRepository;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var EmailRequestItemFactory
     */
    private $emailRequestItemFactory;

    /**
     * @var Url
     */
    private $urlBuilder;

    /**
     * @var UrlCoder
     */
    private $urlCoder;

    public function __construct(
        Repository $statusRepository,
        CustomerRepository $customerRepository,
        OrderRepository $orderRepository,
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider,
        EmailRequestItemFactory $emailRequestItemFactory,
        UrlCoder $urlCoder,
        Url $urlBuilder,
        array $data = []
    ) {
        parent::__construct($data);
        $this->statusRepository = $statusRepository;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->configProvider = $configProvider;
        $this->emailRequestItemFactory = $emailRequestItemFactory;
        $this->urlCoder = $urlCoder;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param \Amasty\Rma\Model\Request\Request $request
     *
     * @return EmailRequest
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function parseRequest($request)
    {
        $order = $this->orderRepository->get($request->getOrderId());
        $status = $this->statusRepository->getById($request->getStatus(), $request->getStoreId());

        $this->setOrderIncrement($order->getIncrementId())
            ->setOrderCreatedAt($order->getCreatedAt())
            ->setOrder($order)
            ->setCustomerName($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname())
            ->setCustomerEmail($order->getCustomerEmail())
            ->setCustomFields($this->prepareCustomFields($request))
            ->setRequestItems($this->prepareReturnItems($request))
            ->setStatus(
                $status->getStore()->getLabel()
            )->setStatusColor($status->getColor())
            ->setRequestId($request->getIncrementId())
            ->setUrl($this->prepareUrl($request));

        return $this;
    }

    /**
     * @param string $orderIncrement
     *
     * @return EmailRequest
     */
    public function setOrderIncrement($orderIncrement)
    {
        return $this->setData(self::ORDER_INCREMENT, $orderIncrement);
    }

    /**
     * @return string
     */
    public function getOrderIncrement()
    {
        return $this->_getData(self::ORDER_INCREMENT);
    }

    /**
     * @param string $date
     *
     * @return EmailRequest
     */
    public function setOrderCreatedAt($date)
    {
        return $this->setData(self::ORDER_CREATED_AT, $date);
    }

    /**
     * @return string
     */
    public function getOrderCreatedAt()
    {
        return $this->_getData(self::ORDER_CREATED_AT);
    }

    public function setOrder(OrderInterface $order): EmailRequest
    {
        return $this->setData(self::ORDER, $order);
    }

    public function getOrder(): OrderInterface
    {
        return $this->_getData(self::ORDER);
    }

    /**
     * @param string $status
     *
     * @return EmailRequest
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->_getData(self::STATUS);
    }

    /**
     * @param string $color
     *
     * @return EmailRequest
     */
    public function setStatusColor($color)
    {
        return $this->setData(self::STATUS_COLOR, $color);
    }

    /**
     * @return string
     */
    public function getStatusColor()
    {
        return $this->_getData(self::STATUS_COLOR);
    }

    /**
     * @param $rmaId
     * @return EmailRequest
     */
    public function setRequestId($rmaId)
    {
        return $this->setData(self::REQUEST_ID, $rmaId);
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return $this->_getData(self::REQUEST_ID);
    }

    /**
     * @param string $customerName
     *
     * @return EmailRequest
     */
    public function setCustomerName($customerName)
    {
        return $this->setData(self::CUSTOMER_NAME, $customerName);
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->_getData(self::CUSTOMER_NAME);
    }

    /**
     * @param string $email
     *
     * @return EmailRequest
     */
    public function setCustomerEmail($email)
    {
        return $this->setData(self::CUSTOMER_EMAIL, $email);
    }

    /**
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->_getData(self::CUSTOMER_EMAIL);
    }

    /**
     * @param array $customFields
     *
     * @return EmailRequest
     */
    public function setCustomFields($customFields)
    {
        return $this->setData(self::CUSTOM_FIELDS, $customFields);
    }

    /**
     * @return array
     */
    public function getCustomFields()
    {
        return $this->_getData(self::CUSTOM_FIELDS);
    }

    /**
     * @param EmailRequestItem[] $items
     *
     * @return EmailRequest
     */
    public function setRequestItems($items)
    {
        return $this->setData(self::REQUEST_ITEMS, $items);
    }

    /**
     * @return EmailRequestItem[]
     */
    public function getRequestItems()
    {
        return $this->_getData(self::REQUEST_ITEMS);
    }

    /**
     * @param string $url
     *
     * @return EmailRequest
     */
    public function setUrl($url)
    {
        return $this->setData(self::URL, $url);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_getData(self::URL);
    }

    /**
     * @param \Amasty\Rma\Model\Request\Request $request
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareCustomFields($request)
    {
        $result = [];
        $configCustomFields = $this->configProvider->getCustomFields($request->getStoreId());

        foreach ($request->getCustomFields() as $field) {
            if (!empty($configCustomFields[$field->getKey()])) {
                $result[] = [
                    'label' => $configCustomFields[$field->getKey()],
                    'value' => $field->getValue()
                ];
            }
        }

        return $result;
    }

    /**
     * @param \Amasty\Rma\Model\Request\Request $request
     *
     * @return EmailRequestItem[]
     */
    private function prepareReturnItems($request)
    {
        $result = [];
        $returnItems = $request->getRequestItems();
        $storeId = $request->getStoreId();

        foreach ($returnItems as $returnItem) {
            $result[] = $this->emailRequestItemFactory->create()
                ->parseRequestItem($returnItem, $storeId);
        }

        return $result;
    }

    /**
     * @param \Amasty\Rma\Model\Request\Request $request
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareUrl($request)
    {
        $this->urlBuilder->setScope($request->getStoreId());
        $baseUrl = $this->storeManager->getStore($request->getStoreId())->getBaseUrl();
        $urlPrefix = $this->configProvider->getUrlPrefix();

        if ($customerId = $request->getCustomerId()) {
            $uenc = $baseUrl . $urlPrefix . '/account/view/request/' . $request->getRequestId();
            $url = $this->urlBuilder->getUrl(
                $this->configProvider->getUrlPrefix() . '/email/login/',
                [
                    RegistryConstants::HASH_PARAM => $request->getUrlHash(),
                    ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlCoder->encode($uenc)
                ]
            );
        } else {
            $url = $baseUrl . $urlPrefix . '/guest/view/request/' . $request->getUrlHash();
        }

        return $url;
    }
}
