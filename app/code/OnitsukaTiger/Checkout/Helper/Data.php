<?php
namespace OnitsukaTiger\Checkout\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Address\CustomerAddressDataFormatter;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\OrderRepository;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Eav\Model\Config;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CustomerAddressDataFormatter
     */
    private $customerAddressDataFormatter;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $_addressRepository;


    /**
     * Data constructor.
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerAddressDataFormatter $customerAddressDataFormatter
     * @param OrderRepository $orderRepository
     * @param CustomerRepository $customerRepository
     * @param CustomerSession $customerSession
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        CustomerAddressDataFormatter $customerAddressDataFormatter,
        protected OrderRepository $orderRepository,
        CustomerRepository $customerRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        CustomerSession $customerSession,
        HttpContext $httpContext,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_orderFactory = $orderFactory;
        $this->productRepository = $productRepository;
        $this->_storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->customerAddressDataFormatter = $customerAddressDataFormatter;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->_addressRepository = $addressRepository;
        $this->httpContext = $httpContext;
        parent::__construct($context);
    }

    /**
     * @param $orderId
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder($orderId) {
        return $this->_orderFactory->create()->load($orderId);
    }

    public function getOrderByIncrementId($orderId){
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);
        return $order;
    }
    /**
     * @return array
     */
    public function getItemOptions($_item)
    {
        $result = [];
        if ($options = $_item->getProductOptions()) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }
        }

        return $result;
    }
    /**
     * @param $config_path
     * @return mixed
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function getProductById($productId) {
        $product = $this->productRepository->getById($productId, false, $this->_storeManager->getStore()->getId());
        return $product;
    }

    /**
     * Retrieve customer data
     *
     * @return array
     */
    public function getCustomerData()
    {
        $customerData = [];
        if ($this->isCustomerLoggedIn()) {
            /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
            $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
            $customerData = $customer->__toArray();
            $customerData['addresses'] = $this->getAddressDataByCustomer($customer);
        }
        return $customerData;
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isCustomerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }
    /**
     * Get addresses for customer.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAddressDataByCustomer(
        \Magento\Customer\Api\Data\CustomerInterface $customer
    ): array {

        $customerOriginAddresses = $customer->getAddresses();
        if (!$customerOriginAddresses) {
            return [];
        }

        $customerAddresses = [];
        foreach ($customerOriginAddresses as $address) {
            $customerAddresses[$address->getId()] = $this->customerAddressDataFormatter->prepareAddress($this->getAddressById($address->getId()));
        }
        return $customerAddresses;
    }
    /**
     * @param $addressId
     * @return \Magento\Customer\Api\Data\AddressInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAddressById($addressId)
    {
        return $this->_addressRepository->getById($addressId);
    }
}
