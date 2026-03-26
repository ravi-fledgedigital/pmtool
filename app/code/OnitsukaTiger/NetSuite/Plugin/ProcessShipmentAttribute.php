<?php

namespace OnitsukaTiger\NetSuite\Plugin;

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
        if (is_callable([$extensionAttributes, 'setShipmentStoreSynced'])) {
            $extensionAttributes->setShipmentStoreSynced($shipmentAttributesModel->getShipmentStoreSynced());
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

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/NetsuiteProcessShipmentAttribute.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Netsuite Process Shipment Attribute Start============================');
        $logger->info('Shipment ID: ' . $shipment->getEntityId());
        $logger->info('==========================Netsuite Process Shipment Attribute End============================');

        $shipmentAttributesModel->setShipmentId((int)$shipment->getEntityId());
        if (!empty($shipment->getExtensionAttributes()->getShipmentStoreSynced())) {
            $shipmentAttributesModel->setShipmentStoreSynced($shipment->getExtensionAttributes()->getShipmentStoreSynced());
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
        if (is_callable([$extensionAttributes, 'setShipmentStoreSynced'])) {
            $extensionAttributes->setShipmentStoreSynced($shipmentAttributesModel->getShipmentStoreSynced());
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
            if (is_callable([$extensionAttributes, 'setShipmentStoreSynced'])) {
                $extensionAttributes->setShipmentStoreSynced($shipmentAttributesModel->getShipmentStoreSynced());
            }
            $shipment->setExtensionAttributes($extensionAttributes);
        }

        return $searchResult;
    }
}
