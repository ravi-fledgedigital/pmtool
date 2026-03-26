<?php

namespace OnitsukaTiger\Shipment\Plugin;

use Magento\Sales\Api\Data\ShipmentSearchResultInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use OnitsukaTiger\Shipment\Model\ShipmentAttributes;
use OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes as ResourceModel;
use OnitsukaTiger\Shipment\Model\ShipmentAttributesFactory as ModelFactory;
use OnitsukaTiger\Shipment\Model\ShipmentAttributes as Model;
use OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes\CollectionFactory;

/**
 * Class ProcessShipmentAttribute
 * @package OnitsukaTiger\Shipment\Plugin
 */
class ProcessShipmentAttribute
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var ModelFactory
     */
    private $modelFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * ProcessExampleAttributes constructor.
     *
     * @param ModelFactory $modelFactory
     * @param ResourceModel $resourceModel
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        ModelFactory $modelFactory,
        ResourceModel $resourceModel,
        CollectionFactory $collectionFactory
    ) {
        $this->resourceModel = $resourceModel;
        $this->modelFactory = $modelFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentInterface $shipment
     *
     * @return ShipmentInterface
     */
    public function afterGet(ShipmentRepositoryInterface $shipmentRepository, ShipmentInterface $shipment)
    {
        $shipmentAttributesModel = $this->getAttributesByShipment($shipment);
        if ($shipmentAttributesModel === null) {
            return $shipment;
        }

        $extensionAttributes = $shipment->getExtensionAttributes();
        if (is_callable([$extensionAttributes, 'setNetsuiteFulfillmentId'])) {
            $extensionAttributes->setNetsuiteFulfillmentId($shipmentAttributesModel->getNetsuiteFulfillmentId());
        }
        if (is_callable([$extensionAttributes, 'setStatus'])) {
            $extensionAttributes->setStatus($shipmentAttributesModel->getStatus());
        }
        if (is_callable([$extensionAttributes, 'setNetsuiteInternalId'])) {
            $extensionAttributes->setNetsuiteInternalId($shipmentAttributesModel->getNetsuiteInternalId());
        }
        if (is_callable([$extensionAttributes, 'setActReleaseDate'])) {
            $extensionAttributes->setActReleaseDate($shipmentAttributesModel->getActReleaseDate());
        }
        if (is_callable([$extensionAttributes, 'setPosReceiptNumber'])) {
            $extensionAttributes->setPosReceiptNumber($shipmentAttributesModel->getPosReceiptNumber());
        }
        if (is_callable([$extensionAttributes, 'setStockPosFlag'])) {
            $extensionAttributes->setStockPosFlag($shipmentAttributesModel->getStockPosFlag());
        }
        $shipment->setExtensionAttributes($extensionAttributes);

        return $shipment;
    }

    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentInterface $shipment
     *
     * @return ShipmentInterface
     * @throws AlreadyExistsException
     */
    public function beforeSave(
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentInterface $shipment
    ) {
        $shipmentAttributesModel = $this->getAttributesByShipment($shipment);
        if (!$shipmentAttributesModel) {
            $shipmentAttributesModel = $this->modelFactory->create();
        }

        $extensionAttributes = $shipment->getExtensionAttributes();
        if ($extensionAttributes === null) {
            return [$shipment];
        }

        $shipmentAttributesModel->setShipmentId((int)$shipment->getEntityId());
        if (!empty($shipment->getExtensionAttributes()->getNetsuiteFulfillmentId())) {
            $shipmentAttributesModel->setNetsuiteFulfillmentId($shipment->getExtensionAttributes()->getNetsuiteFulfillmentId());
        }
        if (!empty($shipment->getExtensionAttributes()->getStatus())) {
            $shipmentAttributesModel->setStatus($shipment->getExtensionAttributes()->getStatus());
        }
        if (!empty($shipment->getExtensionAttributes()->getNetsuiteInternalId())) {
            $shipmentAttributesModel->setNetsuiteInternalId($shipment->getExtensionAttributes()->getNetsuiteInternalId());
        }
        if (!empty($shipment->getExtensionAttributes()->getActReleaseDate())) {
            $shipmentAttributesModel->setActReleaseDate($shipment->getExtensionAttributes()->getActReleaseDate());
        }
        if (!empty($shipment->getExtensionAttributes()->getStockPosFlag())) {
            $shipmentAttributesModel->setStockPosFlag($shipment->getExtensionAttributes()->getStockPosFlag());
        }

        $shipmentAttributesModel->setPosReceiptNumber($shipment->getExtensionAttributes()->getPosReceiptNumber());


        $this->resourceModel->save($shipmentAttributesModel);

        return [$shipment];
    }

    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentInterface $shipment
     *
     * @return ShipmentInterface
     */
    public function afterGetById(ShipmentRepositoryInterface $shipmentRepository, ShipmentInterface $shipment)
    {
        $shipmentAttributesModel = $this->getAttributesByShipment($shipment);
        if ($shipmentAttributesModel === null) {
            return $shipment;
        }

        $extensionAttributes = $shipment->getExtensionAttributes();
        if (is_callable([$extensionAttributes, 'setNetsuiteFulfillmentId'])) {
            $extensionAttributes->setNetsuiteFulfillmentId($shipmentAttributesModel->getNetsuiteFulfillmentId());
        }
        if (is_callable([$extensionAttributes, 'setStatus'])) {
            $extensionAttributes->setStatus($shipmentAttributesModel->getStatus());
        }
        if (is_callable([$extensionAttributes, 'setNetsuiteInternalId'])) {
            $extensionAttributes->setNetsuiteInternalId($shipmentAttributesModel->getNetsuiteInternalId());
        }
        if (is_callable([$extensionAttributes, 'setActReleaseDate'])) {
            $extensionAttributes->setActReleaseDate($shipmentAttributesModel->getActReleaseDate());
        }
        if (is_callable([$extensionAttributes, 'setPosReceiptNumber'])) {
            $extensionAttributes->setPosReceiptNumber($shipmentAttributesModel->getPosReceiptNumber());
        }
        if (is_callable([$extensionAttributes, 'setStockPosFlag'])) {
            $extensionAttributes->setStockPosFlag($shipmentAttributesModel->getStockPosFlag());
        }
        $shipment->setExtensionAttributes($extensionAttributes);

        return $shipment;
    }

    /**
     * @param ShipmentInterface $shipment
     *
     * @return ShipmentAttributes
     */
    private function getAttributesByShipment(ShipmentInterface $shipment): ShipmentAttributes
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('shipment_id', $shipment->getEntityId());

        /** @var ShipmentAttributes $firstItem */
        $firstItem = $collection->getFirstItem();

        return $firstItem;
    }

    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentSearchResultInterface $searchResult
     * @return ShipmentSearchResultInterface
     */
    public function afterGetList(ShipmentRepositoryInterface $shipmentRepository, ShipmentSearchResultInterface $searchResult)
    {
        $shipments = $searchResult->getItems();
        foreach ($shipments as $shipment) {
            $shipmentAttributesModel = $this->getAttributesByShipment($shipment);
            if ($shipmentAttributesModel === null) {
                continue;
            }

            $extensionAttributes = $shipment->getExtensionAttributes();
            if (is_callable([$extensionAttributes, 'setNetsuiteFulfillmentId'])) {
                $extensionAttributes->setNetsuiteFulfillmentId($shipmentAttributesModel->getNetsuiteFulfillmentId());
            }
            if (is_callable([$extensionAttributes, 'setStatus'])) {
                $extensionAttributes->setStatus($shipmentAttributesModel->getStatus());
            }
            if (is_callable([$extensionAttributes, 'setNetsuiteInternalId'])) {
                $extensionAttributes->setNetsuiteInternalId($shipmentAttributesModel->getNetsuiteInternalId());
            }
            if (is_callable([$extensionAttributes, 'setActReleaseDate'])) {
                $extensionAttributes->setActReleaseDate($shipmentAttributesModel->getActReleaseDate());
            }
            if (is_callable([$extensionAttributes, 'setPosReceiptNumber'])) {
                $extensionAttributes->setPosReceiptNumber($shipmentAttributesModel->getPosReceiptNumber());
            }
            if (is_callable([$extensionAttributes, 'setStockPosFlag'])) {
                $extensionAttributes->setStockPosFlag($shipmentAttributesModel->getStockPosFlag());
            }
            $order = $shipment->getOrder();
            $extensionAttributes->setOrderIncrementId($order->getIncrementId());
            if($order->getStoreId() == 8 || $order->getStoreId() == 10) {
                $extensionAttributes->setCustbodyAvnEinvBillingName($order->getBillingAddress()->getFirstname());
                $extensionAttributes->setCustbodyAvnEinvBillingAdd($order->getShippingAddress()->getStreet());
                $shippingStreet = is_array($order->getShippingAddress()->getStreet()) ? implode(", ", $order->getShippingAddress()->getStreet()) : '';
                $extensionAttributes->setCustbodyAvnEinvBillingAdd($shippingStreet);
                $extensionAttributes->setCustbodyAvnEinvBillingVatno($order->getCompanyTaxCode() ?? $order->getTaxId());
                $extensionAttributes->setCustbodyAvnEinvBillingEmail($order->getCustomerEmail());
                $extensionAttributes->setCustbodyAvnEinvBillingPhoneno($order->getBillingAddress()->getTelephone());
                $extensionAttributes->setCustbodyAvnEinvPurchaserName($order->getPurchaserName());
            }
            if ($order->hasInvoices()) {
                $invoice =  $order->getInvoiceCollection()->getFirstItem();
                $extensionAttributes->setInvoiceIncrementId($invoice->getIncrementId());
            }

            $shipment->setExtensionAttributes($extensionAttributes);
        }

        return $searchResult;
    }
}
