<?php

namespace OnitsukaTigerKorea\RmaAddress\Model\OptionSource;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Customer\Model\Address\Mapper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTigerKorea\RmaAddress\Model\ResourceModel\RmaRequestAddress\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;

class Address implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    private $rmaAddressCollection;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Mapper
     */
    protected $addressMapper;

    /**
     * @var AddressConfig
     */
    protected $addressConfig;

    /**
     * Address constructor.
     * @param RequestInterface $request
     * @param OrderRepositoryInterface $orderRepository
     * @param CollectionFactory $rmaAddressCollection
     * @param CustomerRepositoryInterface $customerRepository
     * @param Mapper $addressMapper
     * @param AddressConfig $addressConfig
     */
    public function __construct(
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository,
        CollectionFactory $rmaAddressCollection,
        CustomerRepositoryInterface $customerRepository,
        Mapper $addressMapper,
        AddressConfig $addressConfig
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->rmaAddressCollection = $rmaAddressCollection;
        $this->customerRepository = $customerRepository;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
    }


    public function toOptionArray()
    {
        $optionArray = [];
        foreach ($this->toArray() as $value => $label) {
            $optionArray[] = ['value' => $value, 'label' => mb_substr($label, 0, 80) . '...'];
        }
        return $optionArray;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function toArray()
    {
        $result = [];
        $orderId = $this->request->getParam('order_id');

        if (!$orderId) {
            return $result;
        }

        $order = $this->orderRepository->get($orderId);
        if ($order->getCustomerIsGuest() || !$order->getCustomerId()) {
            $result = $this->getOrderAddressData($order);
        } else {
            $customer = $this->customerRepository->getById($order->getCustomerId());
            if (count($customer->getAddresses())) {
                foreach ($customer->getAddresses() as $address) {
                    $label = $this->addressConfig
                        ->getFormatByCode(AddressConfig::DEFAULT_ADDRESS_FORMAT)
                        ->getRenderer()
                        ->renderArray($this->addressMapper->toFlatArray($address));
                    $result[$address->getId()] = preg_replace('/&nbsp/', '', $label);
                }
            } else {
                $result = $this->getOrderAddressData($order);
            }
        }

        return $result;
    }

    /**
     * @param $order
     * @return array
     */
    private function getOrderAddressData($order)
    {
        $data = [];
        $shippingAddress = $order->getShippingAddress();
        $shippingAddressLabel = $this->addressConfig
            ->getFormatByCode(AddressConfig::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($shippingAddress->getData());

        $data[$shippingAddress->getId()] = $shippingAddressLabel;
        $billingAddress = $order->getBillingAddress();
        $billingAddressLabel = $this->addressConfig
            ->getFormatByCode(AddressConfig::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($billingAddress->getData());
        $data[$billingAddress->getId()] = $billingAddressLabel;
        return $data;
    }
}
