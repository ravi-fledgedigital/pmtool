<?php

namespace OnitsukaTiger\Cegid\Plugin;

use Magento\Sales\Api\Data\ShipmentSearchResultInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use OnitsukaTiger\Cegid\Model\ShipmentUpdate;
use Magento\Framework\App\RequestInterface;
use OnitsukaTiger\Cegid\Service\ApiAction;
use Magento\Framework\App\Request\Http;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;
use Magento\Framework\App\ResourceConnection;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTiger\Cegid\Logger\Logger;

class ProcessShipmentStatus
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ApiAction
     */
    protected $apiAction;

    /**
     * @var ShipmentUpdate
     */
    protected $shipmentUpdate;

    /**
     * @var Http
     */
    protected $httpRequest;

    /**
     * @var StoreShipping
     */
    protected $storeShipping;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Construct
     *
     * @param RequestInterface $request
     * @param ApiAction $apiAction
     * @param ShipmentUpdate $shipmentUpdate
     * @param Http $httpRequest
     * @param StoreShipping $storeShipping
     * @param ResourceConnection $resourceConnection
     * @param Logger $logger
     */
    public function __construct(
        RequestInterface $request,
        ApiAction $apiAction,
        ShipmentUpdate $shipmentUpdate,
        Http $httpRequest,
        StoreShipping $storeShipping,
        ResourceConnection $resourceConnection,
        Logger $logger
    ) {
        $this->request            = $request;
        $this->apiAction          = $apiAction;
        $this->shipmentUpdate     = $shipmentUpdate;
        $this->httpRequest        = $httpRequest;
        $this->storeShipping      = $storeShipping;
        $this->resourceConnection = $resourceConnection;
        $this->logger             = $logger;
    }

    /**
     * After GetList
     *
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentSearchResultInterface $searchResult
     * @return ShipmentSearchResultInterface
     */
    public function afterGetList(
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentSearchResultInterface $searchResult
    ) {
        if ($this->isShipmentApi()) {
            $shipments = $searchResult->getItems();
            $ids       = [];
            foreach ($shipments as $shipment) {
                $ext = $shipment->getExtensionAttributes();
                if (!$this->storeShipping->isShippingFromWareHouse($ext->getSourceCode())) {
                    if (!$shipment->getCegidShipmentStatus()) {
                        $ids[] = $shipment->getEntityId();
                    }
                }
            }
            $connection = $this->resourceConnection->getConnection();
            $table      = $connection->getTableName('sales_shipment');
            if (count($ids) > 0) {
                $where = "`entity_id` IN (" . implode(',', $ids) . ")";
                $connection->update(
                    $table,
                    ['cegid_shipment_status' => ShipmentUpdate::CEGID_SHIPMENT_NO_SYNC],
                    $where
                );
            }
        }

        return $searchResult;
    }

    /**
     * Before Save
     *
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentInterface $shipment
     * @return ShipmentInterface[]
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function beforeSave(
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentInterface $shipment
    ) {
        if ($this->isShipmentApi()) {
            $content = $this->httpRequest->getContent();
            $this->shipmentUpdate->jsonDecode($content);
            if ($this->storeShipping->isShippingFromWareHouse(
                $shipment->getData()['extension_attributes']->getSourceCode()
            )) {
                $this->logger->info('return source code : ' . $shipment->getData()['extension_attributes']->getSourceCode());
                return [$shipment];
            }

            $this->logger->info('----- UpdateShipment() start ----- data : ' . $content);

            $shipment->setOrigData('entity_id', $shipment->getEntityId());
            $shipmentModel = $shipmentRepository->get($shipment->getData()['entity_id']);

            $statusUpdate = $shipment->getData()['extension_attributes']->getStatus();
            $this->logger->info('status update: ' . $statusUpdate);
            $this->apiAction->setShipmentStatusOld($statusUpdate);
            $this->shipmentUpdate->validateShipment($shipmentModel, $statusUpdate);
            $this->logger->info('Shipment status update: ' . $statusUpdate);
            switch ($statusUpdate) {
                case ShipmentUpdate::STATUS_REJECTED:
                    $this->shipmentUpdate->deleteShipment($shipmentModel, $shipment->getData());
                    break;
                case ShipmentUpdate::STATUS_PACKED:
                    $this->logger->info('case packed');
                    $qty = $this->shipmentUpdate->updateShipmentPacked($shipmentModel, $shipment->getData());
                    if ($qty != 0) {
                        $shipment->setTotalQty($shipmentModel->getTotalQty() - $qty);
                    }
                    break;
                case ShipmentUpdate::STATUS_SHIPPED:
                    $this->shipmentUpdate->updateShipmentShipped($shipmentModel, $shipment->getData());
                    break;
                default:
                    break;
            }
        }

        return [$shipment];
    }

    /**
     * Check Call Shipment Api
     *
     * @return bool
     */
    public function isShipmentApi()
    {
        if (strpos($this->request->getRequestString(), ShipmentUpdate::ROUTES_UPDATE_SHIPMENT) !== false) {
            return true;
        }

        return false;
    }
}
