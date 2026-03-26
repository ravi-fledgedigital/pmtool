<?php
namespace OnitsukaTiger\Quote\Plugin\Model\Quote\Address\Total;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Newsletter\Model\Subscriber as CustomerSubscriber;
use Magento\Store\Model\StoreManagerInterface;

class Shipping
{
    public const ENABLE_FREE_SHIPPING_SUBSCRIBER_PATH = "shipping_fee/general_setting/enable";
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var CustomerSubscriber
     */
    private $customerSubscriber;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Construct class
     *
     * @param CustomerSession $customerSession
     * @param CustomerSubscriber $customerSubscriber
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CustomerSession $customerSession,
        CustomerSubscriber $customerSubscriber,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->customerSession = $customerSession;
        $this->customerSubscriber = $customerSubscriber;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Function modified shipping fee when subscriber
     *
     * @param \Magento\Quote\Model\Quote\Address\Total\Shipping $subject
     * @param mixed $result
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterCollect(
        \Magento\Quote\Model\Quote\Address\Total\Shipping $subject,
        $result,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $method = $shippingAssignment->getShipping()->getMethod();
        $address = $shippingAssignment->getShipping()->getAddress();

        $storeId = $this->storeManager->getStore()->getId();
        $enableModule = $this->getValueConfig($storeId);
        $customerId = $this->customerSession->getCustomerId();
        $isCheckCustomerSubscriber =
            $customerId ? $this->customerSubscriber->loadByCustomerId($customerId)->isSubscribed() : false;
        $isCheckCustomerSubscriber =
            $customerId ? true : false;

        if ($method) {
            foreach ($address->getAllShippingRates() as $rate) {
                if ($rate->getCode() == $method && $isCheckCustomerSubscriber && $enableModule) {
                    $total->setBaseShippingAmount(0);
                    $total->setShippingAmount(0);
                }
            }
        }

        return $result;
    }

    /**
     * Get value in configuration
     *
     * @param int $storeId
     * @return mixed
     */
    public function getValueConfig($storeId)
    {
        return $this->scopeConfig->getValue(
            self::ENABLE_FREE_SHIPPING_SUBSCRIBER_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
