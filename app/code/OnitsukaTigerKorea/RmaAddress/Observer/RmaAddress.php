<?php

namespace OnitsukaTigerKorea\RmaAddress\Observer;

use Amasty\Rma\Observer\RmaEventNames;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTigerKorea\RmaAddress\Model\ResourceModel\RmaRequestAddress;
use OnitsukaTigerKorea\RmaAddress\Model\RmaRequestAddressFactory;

class RmaAddress implements ObserverInterface
{
    const RMA_ADDRESS_BY_MANAGER_CREATED = 'manager_rma_created';
    const RMA_ADDRESS_BY_CUSTOMER_CREATED = 'customer_rma_created';
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $customerAddressRepository;

    /**
     * @var OrderAddressRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RmaRequestAddressFactory
     */
    private $rmaRequestAddressFactory;

    /**
     * @var RmaRequestAddress
     */
    private $rmaRequestAddressResource;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \OnitsukaTigerKorea\RmaAddress\Helper\Data
     */
    private  $dataHelper;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;
    /**
     * RmaAddress constructor.
     * @param RequestInterface $request
     * @param OrderRepositoryInterface $orderRepository
     * @param AddressRepositoryInterface $customerAddressRepository
     * @param OrderAddressRepositoryInterface $orderAddressRepository
     * @param RmaRequestAddressFactory $rmaRequestAddressFactory
     * @param RmaRequestAddress $rmaRequestAddressResource
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository,
        AddressRepositoryInterface $customerAddressRepository,
        OrderAddressRepositoryInterface $orderAddressRepository,
        RmaRequestAddressFactory $rmaRequestAddressFactory,
        RmaRequestAddress $rmaRequestAddressResource,
        \OnitsukaTigerKorea\RmaAddress\Helper\Data $dataHelper,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->rmaRequestAddressFactory = $rmaRequestAddressFactory;
        $this->rmaRequestAddressResource = $rmaRequestAddressResource;
        $this->customerRepository = $customerRepository;
        $this->dataHelper = $dataHelper;
        $this->eventManager = $eventManager;
    }

    public function execute(Observer $observer)
    {
        $request = $observer->getRequest();
        $order = $this->orderRepository->get($request->getOrderId());
        if($this->dataHelper->enableShowAddressRMA($order->getStoreId())) {
            $model = $this->rmaRequestAddressFactory->create();
            $model->setRmaRequestId($request->getId());
            if ($this->validateRequestInput($this->request)) {

                $this->setDatabyArray($model, $this->request->getParams());
                $model->setEmail($order->getCustomerEmail());
                $this->rmaRequestAddressResource->save($model);

                if($observer->getEvent()->getName() == RmaEventNames::RMA_CREATED_BY_MANAGER){
                    $this->eventManager->dispatch(self::RMA_ADDRESS_BY_MANAGER_CREATED, ['request' => $request]);
                }else{
                    $this->eventManager->dispatch(self::RMA_ADDRESS_BY_CUSTOMER_CREATED, ['request' => $request]);
                }
                return;
            }
            $rmaAddress = $this->request->getParam('rma_address');
            if (!$rmaAddress) {
                return;
            }

            $model = $this->rmaRequestAddressFactory->create();
            /** @var \Amasty\Rma\Model\Request\Request $request */
            $request = $observer->getRequest();
            $order = $this->orderRepository->get($request->getOrderId());
            $model->setRmaRequestId($request->getId());

            if ($order->getCustomerIsGuest() || !$order->getCustomerId()) {
                $orderAddress = $this->orderAddressRepository->get($rmaAddress);
                $this->setData($model, $orderAddress);
                $model->setEmail($orderAddress->getEmail());
                $model->setRegion($orderAddress->getRegion());
            } else {
                $customer = $this->customerRepository->getById($order->getCustomerId());
                if (count($customer->getAddresses())) {
                    $customerAddress = $this->customerAddressRepository->getById($rmaAddress);
                    $this->setData($model, $customerAddress);
                    $model->setEmail($order->getCustomerEmail());
                    $model->setRegion($customerAddress->getRegion()->getRegion());
                } else {
                    $orderAddress = $this->orderAddressRepository->get($rmaAddress);
                    $this->setData($model, $orderAddress);
                    $model->setEmail($orderAddress->getEmail());
                    $model->setRegion($orderAddress->getRegion());
                }
            }
            $this->rmaRequestAddressResource->save($model);
            if($observer->getEvent()->getName() == RmaEventNames::RMA_CREATED_BY_MANAGER){
                $this->eventManager->dispatch(self::RMA_ADDRESS_BY_MANAGER_CREATED, ['request' => $request]);
            }else{
                $this->eventManager->dispatch(self::RMA_ADDRESS_BY_CUSTOMER_CREATED, ['request' => $request]);
            }
        }
    }


    private function setData($model, $data)
    {
        $model->setFirstname($data->getFirstname());
        $model->setCompany($data->getCompany());
        $model->setStreet($this->implodeStreetValue($data->getStreet()));
        $model->setTelephone($data->getTelephone());
        $model->setPostcode($data->getPostcode());
        $model->setCountryId($data->getCountryId());
        $model->setCity($data->getCity());
        $model->setRegionId($data->getRegionId());
    }

    private function setDataByArray($model, $data)
    {
        $model->setFirstname($data['firstname']);
        $model->setStreet($data['street']);
        $model->setTelephone($data['telephone']);
        $model->setPostcode($data['postcode']);
        $model->setCountryId($data['country_id']);
        $model->setCity($data['city']);
    }

    /**
     * @param $value
     * @return string
     */
    private function implodeStreetValue($value)
    {
        if (is_array($value)) {
            $value = trim(implode(PHP_EOL, $value));
        }
        return $value;
    }

    /**
     * @param $request
     * @return bool
     */
    private function validateRequestInput($request): bool
    {
        $fields = ['firstname', 'street', 'city', 'postcode', 'telephone', 'country_id'];

        foreach ($fields as $key) {
            if ( empty($request->getParam($key)) ) {
                return false;
            }
        }
        return true;
    }

}
