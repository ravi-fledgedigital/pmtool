<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\EmailToWareHouse\Block\Order;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

/**
 * Class Info
 * @package OnitsukaTiger\EmailToWareHouse\Block\Order
 */
class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Directory\Model\Country
     */
    protected $countryModel;

    /**
     * @var \Magento\Directory\Model\Region
     */
    protected $regionModel;

    /**
     * @var \Magento\InventoryApi\Api\SourceRepositoryInterface
     */
    protected $sourceRepository;

    /**
     * @var \OnitsukaTiger\EmailToWareHouse\Service\Email
     */
    protected $_service;
    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @param Template\Context $context
     * @param \Magento\Directory\Model\Country $countryModel
     * @param \Magento\Directory\Model\Region $regionModel
     * @param \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository
     * @param \OnitsukaTiger\EmailToWareHouse\Service\Email $service
     * @param ShipmentRepositoryInterface|null $shipmentRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Directory\Model\Country $countryModel,
        \Magento\Directory\Model\Region $regionModel,
        \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository,
        \OnitsukaTiger\EmailToWareHouse\Service\Email $service,
        ShipmentRepositoryInterface $shipmentRepository,
        array $data = []
    ) {
        $this->countryModel = $countryModel;
        $this->regionModel = $regionModel;
        $this->sourceRepository = $sourceRepository;
        $this->_service = $service;
        $this->shipmentRepository = $shipmentRepository;

        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getShipment()
    {
        $shipment = $this->getData('shipment');
        if ($shipment !== null) {
            return $shipment;
        }
        $shipmentId = (int)$this->getData('shipment_id');

        if ($shipmentId) {
            $shipment = $this->shipmentRepository->get($shipmentId);
        }else{
             $shipment = $this->_service->getVariablesData()['shipment'];
        }
        $this->setData('shipment', $shipment);

        return $this->getData('shipment');
    }
    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return mixed
     */
    public function getToName($shipment)
    {
        $order = $shipment->getOrder();
        $shippingAddress = $order->getShippingAddress();
        return $shippingAddress->getFirstName() . ' ' . $shippingAddress->getLastname();
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return mixed
     */
    public function getToPhone($shipment)
    {
        $order = $shipment->getOrder();
        $shippingAddress = $order->getShippingAddress();

        return $this->phoneNumberFormat($shippingAddress->getTelephone());
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string
     */
    public function getDetailShippingAddress($shipment)
    {
        $order = $shipment->getOrder();
        $shippingAddress = $order->getShippingAddress();

        $detailShippingAddress = '';
        $toCountryName = $this->countryModel->load($shippingAddress->getCountryId())->getName();
        $toRegionName = '';
        if (!is_null($shippingAddress->getRegionId())) {
            $toRegionName = $this->regionModel->load($shippingAddress->getRegionId())->getName() . ', ';
        }
        $company = is_null($shippingAddress->getCompany()) ? '' : $shippingAddress->getCompany() . ', ';
        $detailShippingAddress .= $company . implode(', ', $this->escapeHtml($shippingAddress->getStreet())) . ', ' . $shippingAddress->getCity() . ', ' . $toRegionName . $shippingAddress->getPostCode() . ', ' . $toCountryName;

        return $detailShippingAddress;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWarehouseName($shipment)
    {
        if (empty($shipment)) {
            return 'Shipment is empty';
        }

        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode() ? $shipment->getExtensionAttributes()->getSourceCode() : '';
        if ($sourceCode == '') {
            return '';
        }

        $source = $this->sourceRepository->get($sourceCode);
        return $source->getContactName();
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWarehousePhone($shipment)
    {
        if (empty($shipment)) {
            return 'Shipment is empty';
        }

        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode() ? $shipment->getExtensionAttributes()->getSourceCode() : '';
        if ($sourceCode == '') {
            return '';
        }

        $source = $this->sourceRepository->get($sourceCode);
        if ($source->getPhone() == '') {
            return '';
        }

        return $this->phoneNumberFormat($source->getPhone());
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDetailWarehouseAddress($shipment)
    {
        if (empty($shipment)) {
            return 'Shipment is empty';
        }

        $detailWareHouseAddress = '';
        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode() ? $shipment->getExtensionAttributes()->getSourceCode() : '';
        if ($sourceCode == '') {
            return '';
        }

        $source = $this->sourceRepository->get($sourceCode);
        $wareHouseCountryName = $this->countryModel->loadByCode($source->getCountryId())->getName();
        $wareHouseStreet = is_null($source->getStreet()) ? '' : $source->getStreet() . ', ';
        $wareHouseRegionName = is_null($source->getRegion()) ? '' : $source->getRegion() . ', ';
        $wareHouseCity = is_null($source->getCity()) ? '' : $source->getCity() . ', ';
        $detailWareHouseAddress .= $wareHouseStreet . $wareHouseCity . $wareHouseRegionName . $source->getPostcode() . ', ' . $wareHouseCountryName;

        return $detailWareHouseAddress;
    }

    /**
     * @param string $phoneNumber
     * @return string
     */
    private function phoneNumberFormat(string $phoneNumber): string
    {
        return substr($phoneNumber, 0, 2) . '-' . substr($phoneNumber, 2);
    }
}
