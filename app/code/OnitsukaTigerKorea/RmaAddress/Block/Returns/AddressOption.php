<?php

namespace OnitsukaTigerKorea\RmaAddress\Block\Returns;

use Amasty\Rma\Api\GuestCreateRequestProcessInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Customer\Model\Address\Mapper;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;


class AddressOption extends Template
{
    protected $orderRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var AddressConfig
     */
    protected $addressConfig;

    /**
     * @var Mapper
     */
    protected $addressMapper;

    /**
     * @var GuestCreateRequestProcessInterface
     */
    private $guestCreateRequestProcess;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var bool
     */
    private $isGuest;

    /**
     * AddressOption constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param Mapper $addressMapper
     * @param AddressConfig $addressConfig
     * @param GuestCreateRequestProcessInterface $guestCreateRequestProcess
     * @param CountryFactory $countryFactory
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        Mapper $addressMapper,
        AddressConfig $addressConfig,
        GuestCreateRequestProcessInterface $guestCreateRequestProcess,
        CountryFactory $countryFactory,
        Template\Context $context,
        array $data = []
    )
    {
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->guestCreateRequestProcess = $guestCreateRequestProcess;
        $this->countryFactory = $countryFactory;
        $this->isGuest = !empty($data['isGuest']);
        parent::__construct($context, $data);
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAddressOptions()
    {
        if ($this->isGuest) {
            return $this->getOrderAddressData();
        } else {
            if (count($this->getCustomerAddressData())) {
                return $this->getCustomerAddressData();
            }
            return $this->getOrderAddressData();
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerAddressData()
    {
        $addressData = [];
        $order = $this->getOrder();
        if ($order) {
            $customer = $this->customerRepository->getById($order->getCustomerId());
            foreach ($customer->getAddresses() as $key => $address) {
                $addressData[$address->getId()] = [
                    'name' => $address->getFirstname(),
                    'street' => $address->getStreet(),
                    'post_code' => $address->getPostcode(),
                    'country' => $address->getCountryId(),
                    'telephone' => $address->getTelephone(),
                    'email' => $customer->getEmail()
                ];
            }
        }
        return $addressData;
    }

    public function getOrderAddressData()
    {
        $addressData = [];
        $order = $this->getOrder();
        $customerEmail = $order->getCustomerEmail();

        $shippingAddress = $order->getShippingAddress();
        $addressData[$shippingAddress->getId()] = [
            'name' => $shippingAddress->getFirstname(),
            'street' => $shippingAddress->getStreet(),
            'post_code' => $shippingAddress->getPostcode(),
            'country' => $shippingAddress->getCountryId(),
            'telephone' => $shippingAddress->getTelephone(),
            'email' => $customerEmail
        ];

        $billingAddress = $order->getBillingAddress();
        $addressData[$billingAddress->getId()] = [
            'name' => $billingAddress->getFirstname(),
            'street' => $billingAddress->getStreet(),
            'post_code' => $billingAddress->getPostcode(),
            'country' => $billingAddress->getCountryId(),
            'telephone' => $billingAddress->getTelephone(),
            'email' => $customerEmail
        ];
        return $addressData;
    }

    /**
     * @return OrderInterface|null
     */
    public function getOrder()
    {
        $order = null;
        if ($this->isGuest) {
            $secretKey = $this->getRequest()->getParam('secret');
            if ($secretKey) {
                $orderId = $this->guestCreateRequestProcess->getOrderIdBySecretKey($secretKey);
                $order = $this->orderRepository->get($orderId);
            }
        } else {
            $orderId = $this->getRequest()->getParam('order');
            if ($orderId) {
                $order = $this->orderRepository->get($orderId);
            }
        }

        return $order;
    }

    /**
     * @param $countryCode
     * @return string
     */
    public function getCountryByCode($countryCode)
    {
        /** @var Country $country */
        $country = $this->countryFactory->create();
        return $country->loadByCode($countryCode)->getName();
    }
}
