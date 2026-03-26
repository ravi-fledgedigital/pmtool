<?php
/** phpcs:ignoreFile */

namespace OnitsukaTiger\NetSuite\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use OnitsukaTiger\CancelShipment\Model\Shipment\Cancel as ShipmentCancel;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;

class NetSuite implements \OnitsukaTiger\NetSuite\Api\NetSuiteInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \OnitsukaTiger\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $commonLogger;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $httpRequest;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepositoryInterface;
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;
    /**
     * @var \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku
     */
    protected $getSourceItemBySourceCodeAndSku;
    /**
     * @var \Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory
     */
    protected $sourceItemInterfaceFactory;
    /**
     * @var \Magento\InventoryApi\Api\SourceItemsSaveInterface
     */
    protected $sourceItemsSaveInterface;
    /**
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    protected $orderInterface;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;
    /**
     * @var \Amasty\Rma\Model\Request\Repository
     */
    protected $rmaRepository;
    /**
     * @var \Amasty\Rma\Api\StatusRepositoryInterface
     */
    protected $rmaStatusRepository;
    /**
     * @var SourceMapping
     */
    protected $sourceMapping;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ShipmentStatus
     */
    protected $shipmentStatusModel;

    /**
     * @var OrderStatus
     */
    protected $orderStatusModel;

    /**
     * @var ShipmentCancel
     */
    private $shipmentCancel;

    /**
     * @var Shipment
     */
    protected $shipment;

    /**
     * @var StoreShipping
     */
    protected $storeShipping;

    /**
     * @var
     */
    protected $sourceItemRepository;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var bool
     */
    protected $isShop = false;

    /**
     * @var \OnitsukaTiger\NetSuite\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var SourceRepositoryInterface
     */
    protected $sourceRepository;

    /**
     * NetSuite constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \OnitsukaTiger\Logger\Api\Logger $logger
     * @param \Psr\Log\LoggerInterface $commonLogger
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param \Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory $sourceItemInterfaceFactory
     * @param \Magento\InventoryApi\Api\SourceItemsSaveInterface $sourceItemsSaveInterface
     * @param \Magento\Sales\Api\Data\OrderInterface $orderInterface
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Amasty\Rma\Model\Request\Repository $rmaRepository
     * @param \Amasty\Rma\Api\StatusRepositoryInterface $rmaStatusRepository
     * @param SourceMapping $sourceMapping
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentStatus $shipmentStatusModel
     * @param OrderStatus $orderStatusModel
     * @param ShipmentCancel $shipmentCancel
     * @param Shipment $shipment
     * @param StoreShipping $storeShipping
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param CollectionFactory $collectionFactory
     * @param \OnitsukaTiger\NetSuite\Helper\Data $dataHelper
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface                         $scopeConfig,
        \OnitsukaTiger\Logger\Api\Logger                                           $logger,
        \Psr\Log\LoggerInterface                                                   $commonLogger,
        \Magento\Framework\App\Request\Http                                        $httpRequest,
        \Magento\Sales\Api\OrderRepositoryInterface                                $orderRepository,
        \Magento\Catalog\Model\Product                                             $product,
        \Magento\Catalog\Api\ProductRepositoryInterface                            $productRepositoryInterface,
        \Magento\Sales\Api\ShipmentRepositoryInterface                             $shipmentRepository,
        \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        \Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory                  $sourceItemInterfaceFactory,
        \Magento\InventoryApi\Api\SourceItemsSaveInterface                         $sourceItemsSaveInterface,
        \Magento\Sales\Api\Data\OrderInterface                                     $orderInterface,
        \Magento\Framework\Event\ManagerInterface                                  $eventManager,
        \Amasty\Rma\Model\Request\Repository                                       $rmaRepository,
        \Amasty\Rma\Api\StatusRepositoryInterface                                  $rmaStatusRepository,
        \OnitsukaTiger\NetSuite\Model\SourceMapping                                $sourceMapping,
        SearchCriteriaBuilder                                                      $searchCriteriaBuilder,
        ShipmentStatus                                                             $shipmentStatusModel,
        OrderStatus                                                                $orderStatusModel,
        ShipmentCancel                                                             $shipmentCancel,
        Shipment                                                                   $shipment,
        StoreShipping                                                              $storeShipping,
        SourceItemRepositoryInterface                                              $sourceItemRepository,
        CollectionFactory                                                          $collectionFactory,
        \OnitsukaTiger\NetSuite\Helper\Data                                        $dataHelper,
        SourceRepositoryInterface                                                  $sourceRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->commonLogger = $commonLogger;
        $this->httpRequest = $httpRequest;
        $this->orderRepository = $orderRepository;
        $this->product = $product;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->shipmentRepository = $shipmentRepository;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->sourceItemInterfaceFactory = $sourceItemInterfaceFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->orderInterface = $orderInterface;
        $this->eventManager = $eventManager;
        $this->rmaRepository = $rmaRepository;
        $this->rmaStatusRepository = $rmaStatusRepository;
        $this->sourceMapping = $sourceMapping;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentStatusModel = $shipmentStatusModel;
        $this->orderStatusModel = $orderStatusModel;
        $this->shipmentCancel = $shipmentCancel;
        $this->shipment = $shipment;
        $this->storeShipping = $storeShipping;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->collectionFactory = $collectionFactory;
        $this->dataHelper = $dataHelper;
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * API "Stock Not Available" notification from NetSuite
     * @param string $id
     * @return \OnitsukaTiger\NetSuite\Api\Response\ResponseInterface
     */
    public function orderStockNotAvailable($id)
    {
        $this->logger->info('----- orderStockNotAvailable() start ----- id : ' . $id);

        $shipment = $this->getShipmentByIncrementId($id);
        $this->validateShipment($shipment, [ShipmentStatus::STATUS_PROCESSING]);
        $this->shipmentCancel->execute($shipment);

        $ret = new \OnitsukaTiger\NetSuite\Model\Response\Response(true);
        $this->logger->info('----- orderStockNotAvailable() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * API "Packed" notification from NetSuite
     * @param string $id
     * @param string $fulfillment_id
     * @return \OnitsukaTiger\NetSuite\Api\Response\ResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function orderPacked($id, $fulfillment_id)
    {
        $this->logger->info(sprintf('----- orderPacked() start ----- id [%s] fulfillment_id[%s]', $id, $fulfillment_id));

        $shipment = $this->getShipmentByIncrementId($id);
        $this->validateShipment($shipment, [ShipmentStatus::STATUS_PROCESSING, ShipmentStatus::STATUS_PREPACKED]);

        if ('' == $fulfillment_id) {
            $this->throwWebApiException(sprintf('fulfillment_id is empty'), 400);
        }

        $order = $shipment->getOrder();
        $this->shipmentStatusModel->updateStatus($shipment, ShipmentStatus::STATUS_PREPACKED);
        $this->orderStatusModel->setOrderStatus($order);
        if (!$this->getIsShop()) {
            $this->saveFulfillmentId($shipment, $fulfillment_id);
            $this->logger->info(sprintf('dispatch event : external id[%s] fulfillment_id[%s]', $id, $fulfillment_id));

        } else {
            $this->logger->info(sprintf('dispatch event : external id[%s] to packed by store shipping', $id));
        }
        $this->eventManager->dispatch(self::EVENT_UPDATE_ORDER_STATUS_PREPACKED, ['shipment' => $shipment]);

        $ret = new \OnitsukaTiger\NetSuite\Model\Response\Response(true);
        $this->logger->info('----- orderPacked() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * API "Shipped" notification from NetSuite
     * @param string $id
     * @return \OnitsukaTiger\NetSuite\Api\Response\ShippedResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function orderShipped($id)
    {
        $this->logger->info('----- orderShipped() start ----- id : ' . $id);

        $shipment = $this->getShipmentByIncrementId($id);
        $this->validateShipment($shipment, [ShipmentStatus::STATUS_PACKED]);
        $order = $shipment->getOrder();

        $invoiceNo = '';

        foreach ($order->getInvoiceCollection() as $invoice) {
            $invoiceNo = $invoice->getIncrementId();
        }

        $this->shipmentStatusModel->updateStatus($shipment, ShipmentStatus::STATUS_SHIPPED);
        $this->orderStatusModel->setOrderStatus($order);
        $shippingId = $shipment->getIncrementId();
        $awb = $shipment->getTracksCollection()->getFirstItem()->getTrackNumber();

        $this->eventManager->dispatch(self::EVENT_UPDATE_ORDER_STATUS_SHIPPED, ['shipment' => $shipment]);
        $ret = new \OnitsukaTiger\NetSuite\Model\Response\ShippedResponse(
            true,
            $shippingId,
            $invoiceNo,
            $awb
        );
        $this->logger->info('----- orderShipped() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * API "Cancel" from NetSuite
     * @param string $id
     * @return \OnitsukaTiger\NetSuite\Api\Response\ResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function orderCancel($id)
    {
        $this->logger->info('----- orderCancel() start ----- id : ' . $id);

        $shipment = $this->getShipmentByIncrementId($id);
        // Help be avoid conflict data between shipping company and Magento. So, after ship we should make RMA instead of delete shipment
        $this->validateShipment($shipment, [ShipmentStatus::STATUS_PROCESSING, ShipmentStatus::STATUS_PREPACKED, ShipmentStatus::STATUS_PACKED]);
        $this->shipmentCancel->execute($shipment);

        // dispatch event
        $this->logger->info(sprintf('Process after delete shipment id[%s]', $shipment->getIncrementId()));
        $this->eventManager->dispatch(\OnitsukaTiger\CancelShipment\Model\Shipment\Cancel::AFTER_CANCEL_SHIPMENT, ['shipment' => $shipment]);

        $ret = new \OnitsukaTiger\NetSuite\Model\Response\Response(true);
        $this->logger->info('----- orderCancel() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * API "Return status update" from NetSuite
     * @param string $id
     * @param string $status
     * @return \OnitsukaTiger\NetSuite\Api\Response\ResponseInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function orderReturnStatus($id, $status)
    {
        $this->logger->info(sprintf('----- orderReturnStatus() start ----- id : [%s] status : [%s]', $id, $status));

        /** @var \Amasty\Rma\Model\Request\Request $model */
        $model = $this->rmaRepository->getById($id);
        $status = trim($status);

        if (self::RETURN_STATUS_ACCEPT == $status ||
            self::RETURN_STATUS_ACCEPT_PROCESS_RETURN == $status) {
            $id = $this->scopeConfig->getValue('netsuite/api/rma_accept');
            $itemState = \Amasty\Rma\Model\OptionSource\ItemStatus::RECEIVED;
        } elseif (self::RETURN_STATUS_REJECT == $status ||
            self::RETURN_STATUS_REJECT_PROCESS_RETURN == $status
        ) {
            $id = $this->scopeConfig->getValue('netsuite/api/rma_reject');
            $itemState = \Amasty\Rma\Model\OptionSource\ItemStatus::REJECTED;
        } else {
            $this->throwWebApiException(sprintf('status "%s" is not acceptable', $status), 400);
        }

        // set status
        $status = $this->rmaStatusRepository->getById($id);
        $oldStatus = $model->getStatus();
        $model->setStatus($status->getStatusId());

        // set item status
        foreach ($model->getRequestItems() as $item) {
            $item->setItemStatus($itemState);
        }

        // save
        $this->rmaRepository->save($model);

        // add history
        $this->eventManager->dispatch(
            \Amasty\Rma\Observer\RmaEventNames::STATUS_AUTOMATICALLY_CHANGED,
            ['from' => $oldStatus, 'to' => $status->getStatusId(), 'request' => $model]
        );

        $ret = new \OnitsukaTiger\NetSuite\Model\Response\Response(true);
        $this->logger->info('----- orderReturnStatus() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * API Inventory sync from NetSuite
     * @return \OnitsukaTiger\NetSuite\Api\Response\InventoryResponseInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function inventory()
    {
        $json = $this->httpRequest->getContent();
        $data = $this->jsonDecode($json);

        $this->logger->info('----- inventory() start ----- data : ' . $json);

        $updated = 0;
        $noLocation = 0;
        $skuNotSource = [];
        $sameQty = 0;
        $productSkus = [];

        $convertData = [];
        foreach ($data->products->product as $product) {
            $sourceCode = $this->sourceMapping->getMagentoLocation($product->locationrefcode);
            if (!$sourceCode) {
                $this->logger->warning(__(
                    'cannot find locationrefcode mapping for [%1]',
                    $product->locationrefcode
                ));
                $noLocation++;
                continue;
            }

            $productSkus[] = $product->sku;
            $convertData[$sourceCode][$product->sku] = $product;
        }

        $productCollection = $this->collectionFactory->create();
        $productCollection->addFieldToSelect('sku');
        $productCollection->addFieldToFilter('sku', ['in' => $productSkus]);
        $skuExits = [];
        foreach ($productCollection as $itemProduct) {
            $skuExits[] = $itemProduct->getSku();
        }
        $noSku = array_diff($productSkus, $skuExits);
        $changesSourceItems = [];
        foreach ($convertData as $sourceCode => $products) {
            try {
                $skus = array_keys($products);
                $sourceItems = $this->getSourceItems($sourceCode, $skus);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                foreach ($skus as $sku) {
                    $skuNotSource[] = [
                        'cource_code' => $sourceCode,
                        'sku' => $sku,
                        'stock' => $products[$sku]->stock
                    ];
                }
                $this->logger->info($e->getMessage());
                continue;
            }
            foreach ($sourceItems->getItems() as $sourceItem) {
                if (!in_array($sourceItem->getSku(), $skuExits)) {
                    continue;
                }
                $key = array_search($sourceItem->getSku(), $skus);
                if ($key !== false) {
                    unset($skus[$key]);
                }

                // not update in case current quantity is same with received data
                if ($sourceItem->getQuantity() == $products[$sourceItem->getSku()]->stock) {
                    $this->logger->info(__(
                        'do not need update sku [%1]  NS quantity [%2]',
                        $sourceItem->getSku(),
                        $products[$sourceItem->getSku()]->stock
                    ));
                    $sameQty++;
                    continue;
                }

                $sourceItem = $this->updateProductSource(
                    $sourceItem,
                    $sourceItem->getSourceCode(),
                    $sourceItem->getSku(),
                    $products[$sourceItem->getSku()]->stock
                );
                $changesSourceItems[] = $sourceItem;
                $updated++;
            }
            foreach ($skus as $sku) {
                if (!in_array($sku, $skuExits)) {
                    continue;
                }
                $skuNotSource[] = [
                    'cource_code' => $sourceCode,
                    'sku' => $sku,
                    'stock' => $products[$sku]->stock
                ];
            }
        }

        foreach ($noSku as $sku) {
            $this->logger->warning(sprintf('sku [%s] not found', $sku));
        }

        foreach ($skuNotSource as $item) {
            if (!in_array($item['sku'], $skuExits)) {
                continue;
            }
            if ($item['stock'] == 0) {
                $this->logger->info(
                    sprintf('do not need update sku [%s]  NS quantity [%d]', $item['sku'], $item['stock'])
                );
                $sameQty++;
                continue;
            }
            $sourceItem = $this->sourceItemInterfaceFactory->create();
            $sourceItem = $this->updateProductSource($sourceItem, $item['cource_code'], $item['sku'], $item['stock']);
            $changesSourceItems[] = $sourceItem;
            $updated++;
        }

        if (count($changesSourceItems) > 0) {
            $this->sourceItemsSaveInterface->execute($changesSourceItems);

            /*disable restock flag on stock update start*/
            /*foreach ($data->products->product as $product) {
                $this->dataHelper->disableRestockFlag($product->sku, $product->locationrefcode, $product->stock);
            }*/
            /*disable restock flag on stock update end*/
        }
        $ret = new \OnitsukaTiger\NetSuite\Model\Response\InventoryResponse(
            true,
            $updated,
            $noLocation,
            count($noSku),
            $sameQty
        );
        $this->logger->info('----- inventory() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * API InternalId sync from NetSuite
     * @return \OnitsukaTiger\NetSuite\Api\Response\ProductInternalIdResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function productsInternalId()
    {
        $json = $this->httpRequest->getContent();
        $data = $this->jsonDecode($json);

        $this->logger->info('----- productsInternalId() start ----- data : ' . $json);

        $updated = 0;
        $noSku = 0;
        $idExist = 0;

        foreach ($data->products->product as $data) {
            // SKU check
            if (!$this->product->getIdBySku($data->sku)) {
                $this->logger->warning(sprintf('sku [%s] not found', $data->sku));
                $noSku++;
                continue;
            }

            $product = $this->productRepositoryInterface->get($data->sku, true, \Magento\Store\Model\Store::DEFAULT_STORE_ID);

            // skip if id is exist already
            $id = $product->getData('netsuite_internal_id');
            if ($id == $data->skuid) {
                $this->logger->info(sprintf('do not need update sku [%s]', $data->sku));
                $idExist++;
                continue;
            }
            // udpate
            $product->setData('netsuite_internal_id', $data->skuid);
            $this->logger->info(sprintf('sku [%s] internal id update to [%s]', $data->sku, $data->skuid));

            $product->save();
            $updated++;
        }

        $ret = new \OnitsukaTiger\NetSuite\Model\Response\ProductInternalIdResponse(
            true,
            $updated,
            $noSku,
            $idExist
        );
        $this->logger->info('----- productsInternalId() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * @param \Magento\Sales\Api\Data\ShipmentInterface $shipment
     * @param array $status
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function validateShipment(\Magento\Sales\Api\Data\ShipmentInterface $shipment, array $status)
    {
        $ext = $shipment->getExtensionAttributes();

        if (!$this->isShop && !$this->storeShipping->isShippingFromWareHouse($ext->getSourceCode())) {
            $this->throwWebApiException(sprintf('shipment id [%s] is not belong to warehouse', $shipment->getIncrementId()), 400);
        }
        if (!in_array($ext->getStatus(), $status)) {
            $this->throwWebApiException(sprintf('AWB number is already generated for the [Shipment ID:%s]', $shipment->getIncrementId(), implode(', ', $status)), 400);
        }
    }

    /**
     * Throw Web API exception and add it to log
     * @param $msg
     * @param $status
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function throwWebApiException($msg, $status)
    {
        $exception = new \Magento\Framework\Webapi\Exception(__($msg), $status);
        $this->commonLogger->critical($exception);
        throw $exception;
    }

    /**
     * check and decode json string
     * @param $string
     * @return mixed
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function jsonDecode($string)
    {
        $data = json_decode($string);
        // decode error
        if (!$data) {
            $this->throwWebApiException('invalid json format', 400);
        }
        // index check
        if (!property_exists($data, 'products')) {
            $this->throwWebApiException('invalid json format', 400);
        }
        if (!property_exists($data->products, 'product')) {
            $this->throwWebApiException('invalid json format', 400);
        }
        return $data;
    }

    /**
     * @param $id
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function getShipmentByIncrementId($id)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $id)->create();
        $shipments = $this->shipmentRepository->getList($searchCriteria)->getItems();

        if (count($shipments)) {
            return array_values($shipments)[0];
        } else {
            $this->throwWebApiException(sprintf('external id format is wrong [%s]', $id), 400);
        }
    }

    public function setIsShop()
    {
        $this->isShop = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsShop()
    {
        return $this->isShop;
    }

    /**
     * Returns source item by skus
     *
     * @param string $sourceCode
     * @param array $skus
     * @return \Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSourceItems($sourceCode, $skus)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\Magento\InventoryApi\Api\Data\SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->addFilter(\Magento\InventoryApi\Api\Data\SourceItemInterface::SKU, $skus, 'in')
            ->create();
        $sourceItemsResult = $this->sourceItemRepository->getList($searchCriteria);
        if ($sourceItemsResult->getTotalCount() === 0) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('Source item not found by source code: %1 and sku: %2.', $sourceCode, json_encode($skus))
            );
        }
        return $sourceItemsResult;
    }

    /**
     * Update source item for product
     *
     * @param SourceItemInterface $sourceItem
     * @param string $sourceCode
     * @param string $sku
     * @param int $stock
     * @return SourceItemInterface
     */
    public function updateProductSource($sourceItem, $sourceCode, $sku, $stock)
    {
        $isModuleEnable = $this->scopeConfig->getValue('netsuite/general/is_enable_exclude_skus_from_inventory_sync');
        if ($isModuleEnable) {
            $sourceCodeData = $this->sourceRepository->get($sourceCode);
            if (!empty($sourceCodeData->getExcludeSkusFromInventorySync()) &&
                in_array($sku, explode(",", $sourceCodeData->getExcludeSkusFromInventorySync()))) {
                $sourceItem->setStatus(0);
            } else {
                $sourceItem->setStatus($stock > 0);
                $sourceItem->setQuantity($stock);
            }
            $sourceItem->setSourceCode($sourceCode);
            $sourceItem->setSku($sku);
        } else {
            $sourceItem->setSourceCode($sourceCode);
            $sourceItem->setSku($sku);
            $sourceItem->setQuantity($stock);
            $sourceItem->setStatus($stock > 0);
        }

        $this->logger->info(__(
            'sku [%1] quantity update to [%2]',
            $sku,
            $stock
        ));
        return $sourceItem;
    }

    /**
     * @param $shipment
     * @param $fulfillment_id
     * @return void
     */
    private function saveFulfillmentId($shipment, $fulfillment_id)
    {
        $ext = $shipment->getExtensionAttributes();
        $ext->setNetsuiteFulfillmentId($fulfillment_id);
        $shipment->setExtensionAttributes($ext);
        $this->shipmentRepository->save($shipment);
    }
}
