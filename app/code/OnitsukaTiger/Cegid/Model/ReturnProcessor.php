<?php
namespace OnitsukaTiger\Cegid\Model;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\StatusInterface;
use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory;
use Amasty\Rma\Model\Request\ResourceModel\RequestItemCollectionFactory;
use Amasty\Rma\Model\Status\ResourceModel\Status;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order\Shipment as OrderShipment;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Item\Collection as ShipmentItemCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Weee\Helper\Data;
use OnitsukaTiger\Cegid\Logger\Logger;
use OnitsukaTiger\Cegid\Model\Service\CegidApiService;

class ReturnProcessor
{
    const WAREHOUSE_CODE = [
        'SGGLOB' => 'SOM09M',
        'MYGLOB' => 'MOM02M',
        'THGLOB' => 'TOT07M',
        'VNGLOB' => 'VOV02M'
    ];

    const CUSTOMER_ID = [
        'SG' => 'SGGLOB0000003',
        'MY' => 'MYGLOB0000002',
        'TH' => 'THGLOB0000002',
        'VN' => 'VNGLOB0000001'
    ];

    const STORE_CODE = [
        'SG' => 'SGGLOB',
        'MY' => 'MYGLOB',
        'TH' => 'THGLOB',
        'VN' => 'VNGLOB'
    ];

    private CegidApiService $apiService;
    private CollectionFactory $collectionFactory;
    private Config $config;
    private StoreManagerInterface $storeManager;
    private CurrencyFactory $currencyFactory;
    private ShipmentCollectionFactory $shipmentCollectionFactory;
    private RequestItemCollectionFactory $requestItemCollectionFactory;
    private OrderItemRepositoryInterface $orderItemRepository;
    private ProductRepositoryInterface $productRepository;
    private OrderItemCollectionFactory $orderItemCollectionFactory;
    private Configurable $configurable;
    private TimezoneInterface $timezone;
    private Logger $logger;
    private Data $weeeHelper;
    private OrderShipment $shipment;
    private ShipmentItemCollection $shipmentItemCollection;
    private SerializerInterface $serializer;

    /**
     * @param CegidApiService $apiService
     * @param CollectionFactory $collectionFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param CurrencyFactory $currencyFactory
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     * @param RequestItemCollectionFactory $requestItemCollectionFactory
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param Configurable $configurable
     * @param TimezoneInterface $timezone
     * @param Logger $logger
     * @param Data $weeeHelper
     * @param OrderShipment $shipment
     * @param ShipmentItemCollection $shipmentItemCollection
     * @param SerializerInterface $serializer
     */
    public function __construct(
        CegidApiService     $apiService,
        CollectionFactory   $collectionFactory,
        Config              $config,
        StoreManagerInterface   $storeManager,
        CurrencyFactory     $currencyFactory,
        ShipmentCollectionFactory   $shipmentCollectionFactory,
        RequestItemCollectionFactory       $requestItemCollectionFactory,
        OrderItemRepositoryInterface    $orderItemRepository,
        ProductRepositoryInterface      $productRepository,
        OrderItemCollectionFactory      $orderItemCollectionFactory,
        Configurable                    $configurable,
        TimezoneInterface               $timezone,
        Logger                          $logger,
        Data                            $weeeHelper,
        OrderShipment                   $shipment,
        ShipmentItemCollection          $shipmentItemCollection,
        SerializerInterface             $serializer
    ) {
        $this->apiService = $apiService;
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->currencyFactory = $currencyFactory;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->requestItemCollectionFactory = $requestItemCollectionFactory;
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->configurable = $configurable;
        $this->timezone = $timezone;
        $this->logger = $logger;
        $this->weeeHelper = $weeeHelper;
        $this->shipment = $shipment;
        $this->shipmentItemCollection = $shipmentItemCollection;
        $this->serializer = $serializer;
    }

    /**
     * @return string|void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function execute()
    {
        $this->logger->info('----- Start call Api Create----- ');
        $databaseId = $this->config->getReturnDatabaseId();
        $collection = $this->collectionFactory->create();
        $stores = $this->storeManager->getStores(true, true);

        $collection->addFilterToMap('status', 'main_table.status')
            ->addFilterToMap('store_id', 'main_table.store_id')
            ->addFieldToFilter('status', $this->config->getReturnStatusSendToCegid())
            ->addFieldToFilter('store_id', [
                'in' => [
                    $stores['web_sg_en']->getStoreId(),
                    $stores['web_my_en']->getStoreId(),
                    $stores['web_th_en']->getStoreId(),
                    $stores['web_th_th']->getStoreId(),
                    $stores['web_vn_en']->getStoreId(),
                    $stores['web_vn_vi']->getStoreId()
                ]
            ])
            ->join(
                'sales_order',
                'main_table.' . RequestInterface::ORDER_ID . ' = sales_order.entity_id',
                ['sales_order.increment_id']
            )->join(
                ['st' => $collection->getTable(Status::TABLE_NAME)],
                'main_table.' . RequestInterface::STATUS . ' = st.' . StatusInterface::STATUS_ID,
                ['st.' . StatusInterface::STATE]
            );
        foreach ($collection as $item) {
            $sourceWH = $this->getWarehouseCode($item->getShipmentIncrementId());
            if ($sourceWH == null) {
                $this->logger->info('----- Increment Id ' . $item->getShipmentIncrementId() . ' does not exit  ----- ');
                continue;
            }
            $this->logger->info('----- Request  Id ' . $item->getRequestId() . ' sending ----- ');
            $currencySymbols = $this->getCurrencySymbols($item->getStoreId());
            $storeCode = $this->getStoreCode($item->getStoreId());
            $customerId = $this->getCustomerId($item->getStoreId());
            $date = date_format(new \DateTime($item->getCreatedAt()), 'Y-m-d');
            $requestItemCollection = $this->getRequestItemCollection($item->getRequestId(), self::WAREHOUSE_CODE[$storeCode], $item->getShipmentIncrementId());
            $xmlSentToApi =
                '<soapenv:Envelope
                    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                    xmlns:ns="http://www.cegid.fr/Retail/1.0">
                    <soapenv:Header/>
                    <soapenv:Body>
                        <ns:Create>
                            <ns:Request>
                                <ns:Header>
                                    <ns:CurrencyId>' . $currencySymbols . '</ns:CurrencyId>
                                    <ns:CustomerIdentifier>
                                        <ns:Id>' . $customerId . '</ns:Id>
                                    </ns:CustomerIdentifier>
                                    <ns:Date>' . $date . '</ns:Date>
                                    <ns:DocumentTypeToCreate>SentTransfer</ns:DocumentTypeToCreate>
                                    <ns:ExternalReference>' . $item->getIncrementId() . '</ns:ExternalReference>
                                    <ns:PreOrder>' . $item->getIsPreOrder() . '</ns:PreOrder>
                                    <ns:FollowUpReference>' . $item->getShipmentIncrementId() . '</ns:FollowUpReference>
                                    <ns:InternalReference>' . $item->getRequestId() . '</ns:InternalReference>
                                    <ns:Recipient>
                                        <ns:StoreId>' . trim($sourceWH['store']) . '</ns:StoreId>
                                        <ns:WarehouseId>' . trim($sourceWH['wh_code']) . '</ns:WarehouseId>
                                    </ns:Recipient>
                                    <ns:Sender>
                                        <ns:StoreId>' . $storeCode . '</ns:StoreId>
                                        <ns:WarehouseId>' . self::WAREHOUSE_CODE[$storeCode] . '</ns:WarehouseId>
                                    </ns:Sender>
                                    <ns:TaxIncluded>1</ns:TaxIncluded>
                                    <ns:UserDefinedTables>
                                        <!--Zero or more repetitions:-->
                                        <ns:UserDefinedTable>
                                            <ns:Id>1</ns:Id>
                                            <ns:Value>A01WEB</ns:Value>
                                        </ns:UserDefinedTable>
                                    </ns:UserDefinedTables>
                                </ns:Header>
                                <ns:Lines>
                                    <!--Zero or more repetitions:-->
                                    ' . $requestItemCollection . '
                                </ns:Lines>
                            </ns:Request>
                            <ns:Context>
                                <ns:DatabaseId>' . $databaseId . '</ns:DatabaseId>
                            </ns:Context>
                        </ns:Create>
                    </soapenv:Body>
                </soapenv:Envelope>';
            $this->logger->info('----- Create file Xml create success ----- ');
            $this->logger->info('----- Body data ----- ' . $xmlSentToApi);
            $this->apiService->getReturnInformation($xmlSentToApi, $item->getRequestId());
        }
    }

    /**
     * @param $storeId
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrencySymbols($storeId): string
    {
        $storeManager = $this->storeManager->getStore($storeId);
        return $storeManager->getCurrentCurrency()->getCurrencyCode();
    }

    /**
     * @param $storeId
     * @return string|void
     * @throws NoSuchEntityException
     */
    public function getStoreCode($storeId)
    {
        $this->logger->info('----- Get store code ----- ');
        $storeManagerCode = $this->storeManager->getStore($storeId)->getCode();
        if ($storeManagerCode == 'web_sg_en') {
            return self::STORE_CODE['SG'];
        } elseif ($storeManagerCode == 'web_th_en' || $storeManagerCode == 'web_th_th') {
            return self::STORE_CODE['TH'];
        } elseif ($storeManagerCode == 'web_my_en') {
            return self::STORE_CODE['MY'];
        } elseif ($storeManagerCode == 'web_vn_en' || $storeManagerCode == 'web_vn_vi') {
            return self::STORE_CODE['VN'];
        }
    }

    /**
     * @param $incrementId
     * @return string[]|null
     */
    public function getWarehouseCode($shipmentIncrementId): ?array
    {
        $this->logger->info('----- Start check Source code information ----- ');
        $shipmentCollection = $this->shipmentCollectionFactory->create();
        $shipmentCollection->addFieldToFilter('increment_id', $shipmentIncrementId);
        $shipmentCollection->join(
            'inventory_shipment_source',
            'main_table.' . 'entity_id' . ' = inventory_shipment_source.shipment_id',
        );
        foreach ($shipmentCollection as $shipmentItem) {
            if (strpos($shipmentItem->getSourceCode(), 'ps') || $shipmentItem->getSourceCode() == 'VOS') {
                $this->logger->info('----- Get source code POS' . $shipmentItem->getSourceCode() . ' ----- ');
                return $this->getCegidSourceMapping($shipmentItem->getSourceCode());
            }
        }
        return null;
    }
    /**
     * Get data source mapping
     * @param $sourceCode
     * @return mixed|null
     */
    public function getCegidSourceMapping($sourceCode)
    {
        $data = null;
        try {
            $sourceMapping = $this->config->getCegidSourceMapping();
            $result = $this->serializer->unserialize($sourceMapping);
            foreach ($result as $item) {
                if ($item['source_code'] == $sourceCode) {
                    $data = $item;
                    break;
                }
            }
            return $data;
        } catch (Exception $exception) {
            $this->logger->info('----- Get Information Source Mapping error ----- ');
            return $data;
        }
    }

    /**
     * @param $requestId
     * @param $whCode
     * @param $shipmentIncrementId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getRequestItemCollection($requestId, $whCode, $shipmentIncrementId): string
    {
        $this->logger->info('----- Request Item Information ----- ');
        $requestItemCollection = $this->requestItemCollectionFactory->create();
        $requestItemCollection->addFieldToFilter("request_id", $requestId);
        $requestItem = '';
        $i = 1;
        foreach ($requestItemCollection as $item) {
            $productInfo = $this->getProductInformation($item->getOrderItemId(), $shipmentIncrementId);
            $requestItem.=
                '<ns:Line>
                    <ns:ExternalReference>' . $productInfo['price_incl_tax'] . '</ns:ExternalReference>
                    <ns:ItemIdentifier>
                        <ns:Reference>' . $productInfo['ean_code'] . '</ns:Reference>
                    </ns:ItemIdentifier>
                    <ns:Quantity>' . $item->getQty() . '</ns:Quantity>
                    <ns:SenderWarehouseId>' . $whCode . '</ns:SenderWarehouseId>
                    <ns:UnitPriceBase>' . $productInfo['price'] . '</ns:UnitPriceBase>
                </ns:Line>';
            $i++;
        }
        return $requestItem;
    }

    /**
     * @param $orderItemId
     * @param $shipmentIncrementId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductInformation($orderItemId, $shipmentIncrementId): array
    {
        $this->logger->info('----- Product Information by order Item Id ----- ');
        $orderItemInfo = $this->orderItemRepository->get($orderItemId);
        $orderParentItem = $this->orderItemRepository->get($orderItemInfo->getParentItemId());
        $productInfo = $this->productRepository->getById($orderItemInfo->getProductId());

        $shipment = $this->shipment->loadByIncrementId($shipmentIncrementId);
        $shipmentItemCollection = $this->shipmentItemCollection
            ->addFieldToFilter("parent_id", $shipment->getEntityId())
            ->addFieldToFilter("product_id", $orderParentItem->getProductId())
            ->addFieldToFilter("order_item_id", $orderParentItem->getItemId())
            ->getFirstItem();
        $rowTotal = $this->getTotalAmount($orderParentItem);
        $this->logger->info('----- Row Total :  ----- ' . $rowTotal);
        return [
            'price' => number_format(
                $rowTotal/ $orderItemInfo->getQtyOrdered(),
                4,
                '.',
                ''
            ),
            'ean_code' => $productInfo->getEanCode(),
            'price_incl_tax' =>$orderParentItem->getPriceInclTax(),
        ];
    }

    /**
     * @param $storeId
     * @return string|void
     * @throws NoSuchEntityException
     */
    public function getCustomerId($storeId)
    {
        $this->logger->info('----- Customer Id by store ----- ');
        $storeManagerCode = $this->storeManager->getStore($storeId)->getCode();
        if ($storeManagerCode == 'web_sg_en') {
            return self::CUSTOMER_ID['SG'];
        } elseif ($storeManagerCode == 'web_th_en' || $storeManagerCode == 'web_th_th') {
            return self::CUSTOMER_ID['TH'];
        } elseif ($storeManagerCode == 'web_my_en') {
            return self::CUSTOMER_ID['MY'];
        } elseif ($storeManagerCode == 'web_vn_en' || $storeManagerCode == 'web_vn_vi') {
            return self::CUSTOMER_ID['VN'];
        }
    }

    /**
     * Return the total amount minus discount
     *
     * @param mixed $item
     * @return int|float
     */
    public function getTotalAmount(mixed $item): int|float
    {
        $totalAmount = $item->getRowTotal()
            - $item->getDiscountAmount()
            + $item->getTaxAmount()
            + $item->getDiscountTaxCompensationAmount()
            + $this->weeeHelper->getRowWeeeTaxInclTax($item);
        $this->logger->info('----- Row Weee Tax Incl Tax :  ----- ' . $this->weeeHelper->getRowWeeeTaxInclTax($item));

        return $totalAmount;
    }
}
