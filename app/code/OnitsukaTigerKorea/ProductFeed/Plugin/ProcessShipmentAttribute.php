<?php

namespace OnitsukaTigerKorea\ProductFeed\Plugin;

use Magento\Sales\Api\Data\ShipmentSearchResultInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use OnitsukaTigerKorea\Sales\Model\Shipment\ShipmentAttributes;
use OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes as ResourceModel;
use OnitsukaTigerKorea\Sales\Model\Shipment\ShipmentAttributesFactory as ModelFactory;
use OnitsukaTigerKorea\Sales\Model\Shipment\ShipmentAttributes as Model;
use OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes\CollectionFactory;

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
        if (is_callable([$extensionAttributes, 'setExportSaleDataFlag'])) {
            $extensionAttributes->setExportSaleDataFlag($shipmentAttributesModel->getExportSaleDataFlag());
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
        if (!empty($shipment->getExtensionAttributes()->getExportSaleDataFlag())) {
            $shipmentAttributesModel->setExportSaleDataFlag($shipment->getExtensionAttributes()->getExportSaleDataFlag());
        }

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
        if (is_callable([$extensionAttributes, 'setExportSaleDataFlag'])) {
            $extensionAttributes->setExportSaleDataFlag($shipmentAttributesModel->getExportSaleDataFlag());
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
            if (is_callable([$extensionAttributes, 'setExportSaleDataFlag'])) {
                $extensionAttributes->setExportSaleDataFlag($shipmentAttributesModel->getExportSaleDataFlag());
            }
            $shipment->setExtensionAttributes($extensionAttributes);
        }

        return $searchResult;
    }
}
