<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export;

use Amasty\Rma\Model\Request\Repository as RmaRepository;
use Amasty\Rma\Model\Request\Request;
use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory as RmaCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Xml\Parser;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditMemoCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTiger\OrderStatus\Model\Shipment;
use OnitsukaTigerKorea\SftpImportExport\Helper\Data as KoreaSftpHelperData;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\ExportXml;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\PrepareData;

class SalesData
{
    const PATH_SALE_DATA = 'Sales_Data';
    const PATH_ORD_SALE_DATA = 'Ord_Sales_Data';
    const TYPE_NAME_SALE_DATA = 'OKR_SalesD';
    const TYPE_NAME_SALE_DATA_RP = 'OKR_SalesRp';
    const NEED_TO_EXPORT = 0;

    const EXPORTED = 1;

    const EXPORT_PATH = '/var/shared/sftp/export/salesdata/';

    protected int $index = 1;

    protected array $entityExportOrderData = [];

    protected array $entityExportRmaData = [];

    /**
     * @var ExportXml
     */
    protected ExportXml $exportXml;

    /**
     * @var Shipment
     */
    protected Shipment $shipmentModel;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected ShipmentRepositoryInterface $shipmentRepository;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @var PrepareData
     */
    protected PrepareData $prepareData;

    /**
     * @var RmaRepository
     */
    protected RmaRepository $rmaRepository;

    /**
     * @var RmaCollectionFactory
     */
    protected RmaCollectionFactory $rmaCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var ShipmentCollectionFactory
     */
    protected ShipmentCollectionFactory $shipmentCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    protected TimezoneInterface $localeDate;

    /**
     * @var KoreaSftpHelperData
     */
    protected KoreaSftpHelperData $koreaSftpHelperData;

    /**
     * @var int
     */
    public $storeId = 1;

    /**
     * @var DirectoryList
     */
    protected DirectoryList $_dir;

    /**
     * @var CreditMemoCollectionFactory
     */
    protected CreditMemoCollectionFactory $creditMemoCollectionFactory;

    /**
     * @var Parser
     */
    private Parser $parser;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * Cancel constructor.
     * @param DirectoryList $dir
     * @param ExportXml $exportXml
     * @param Shipment $shipmentModel
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Logger $logger
     * @param PrepareData $prepareData
     * @param RmaRepository $rmaRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param RmaCollectionFactory $rmaCollectionFactory
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     * @param TimezoneInterface $localeDate
     * @param KoreaSftpHelperData $koreaSftpHelperData
     * @param CreditMemoCollectionFactory $creditMemoCollectionFactory
     * @param Parser $parser
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        DirectoryList $dir,
        ExportXml $exportXml,
        Shipment $shipmentModel,
        ShipmentRepositoryInterface $shipmentRepository,
        Logger $logger,
        PrepareData $prepareData,
        RmaRepository $rmaRepository,
        ScopeConfigInterface $scopeConfig,
        RmaCollectionFactory $rmaCollectionFactory,
        ShipmentCollectionFactory $shipmentCollectionFactory,
        TimezoneInterface $localeDate,
        KoreaSftpHelperData $koreaSftpHelperData,
        CreditMemoCollectionFactory $creditMemoCollectionFactory,
        Parser  $parser,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder   $searchCriteriaBuilder
    ) {
        $this->_dir = $dir;
        $this->exportXml = $exportXml;
        $this->shipmentModel = $shipmentModel;
        $this->shipmentRepository = $shipmentRepository;
        $this->logger = $logger;
        $this->prepareData = $prepareData;
        $this->rmaRepository = $rmaRepository;
        $this->rmaCollectionFactory = $rmaCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->localeDate = $localeDate;
        $this->koreaSftpHelperData = $koreaSftpHelperData;
        $this->creditMemoCollectionFactory = $creditMemoCollectionFactory;
        $this->parser = $parser;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function execute()
    {
        $salesDataArray = $this->convertXmlFileToArray(self::TYPE_NAME_SALE_DATA, self::PATH_SALE_DATA);
        $ordSalesDataArray = $this->convertXmlFileToArray(self::TYPE_NAME_SALE_DATA_RP, self::PATH_ORD_SALE_DATA);

        $salesData['Sales_Data'] = $salesDataArray['data'] ?? [];
        $this->entityExportOrderData = $salesDataArray['shipmentIds'] ?? [];
        $this->storeId = $salesDataArray['storeId'] ?? $this->getStoreId();
        $ordSalesData['Ord_Sales_Data'] = $ordSalesDataArray['data'] ?? [];
        $salesDataArrayFile = $salesDataArray['fileName'] ?? [];
        $ordSalesDataArrayFile = $ordSalesDataArray['fileName'] ?? [];
        $removeListFile = array_merge($salesDataArrayFile, $ordSalesDataArrayFile);


        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/okrRmaOrderSalesDataLog.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Return Sales Data Log Start============================');
        $logger->info('Store ID: ' . $this->getStoreId());
        $logger->info('Allowed Status: ' . $this->koreaSftpHelperData->getAllowedRmaStatusExportData());
        $logger->info('Export Sales Data Flag: ' . self::NEED_TO_EXPORT);

        $rmaRequests = $this->rmaCollectionFactory->create();
        $rmaRequests->addFieldToFilter('store_id', $this->getStoreId());
        $rmaRequests->addFieldToFilter('status', $this->koreaSftpHelperData->getAllowedRmaStatusExportData());
        $rmaRequests->addFieldToFilter('export_sale_data_flag', self::NEED_TO_EXPORT);
        $logger->info('Rma Collection: ' . json_encode($rmaRequests->getData()));
        foreach ($rmaRequests as $request) {
            /** @var Request $rmaModel */
            $rmaModel = $this->rmaRepository->getById($request->getRequestId());
            $creditMemos = $this->creditMemoCollectionFactory->create()
                ->addFieldToFilter('rma_request_id', $request->getId());
            $logger->info('Credit Memo Count: ' . $creditMemos->count());
            if ($creditMemos->getItems()) {
                $logger->info('Credit memo generated');
                $logger->info('Request ID: ' . $request->getRequestId());
                $salesData['Sales_Data'][] = $this->prepareData->getRmaSalesData($rmaModel);
                $ordSalesData['Ord_Sales_Data'][] = $this->prepareData->getRmaOrdSalesData($rmaModel);
                $logger->info('Sale Data: ' . json_encode($salesData['Sales_Data']));
                $logger->info('Ord Sales Data: ' . json_encode($ordSalesData['Ord_Sales_Data']));
                $this->entityExportRmaData[] = $request->getRequestId();
                $this->storeId = $request->getStoreId();
            }
        }

        if (empty($this->entityExportRmaData) && empty($this->entityExportOrderData)) {
            $this->logger->info('SFTP Export Sales Data: No data to export');
            return 'SFTP Export Sales Data: No data to export';
        }
        $rootDir = $this->_dir->getRoot();

        try {
            if (!file_exists($rootDir . self::EXPORT_PATH)) {
                mkdir($rootDir . self::EXPORT_PATH, 0777, true);
            }

            $path = $rootDir . self::EXPORT_PATH;

            $timeZoneDatetimeString = $this->exportXml->getTimeZoneDatetimeString('YmdHis', $this->storeId);

            $fileNameOfSalesData = 'OKR_SalesD_' . $timeZoneDatetimeString . '.xml';
            $logger->info('Sales Data Filename: ' . $fileNameOfSalesData);
            $this->exportXml->exportToFileXml($salesData, $path . $fileNameOfSalesData);
            $this->logger->info('exported : ' . $fileNameOfSalesData);

            $fileNameOfOrdSalesData = 'OKR_SalesRp_' . $timeZoneDatetimeString . '.xml';
            $logger->info('Sales Data R: ' . $fileNameOfOrdSalesData);
            $this->exportXml->exportToFileXml($ordSalesData, $path . $fileNameOfOrdSalesData);
            $this->logger->info('exported : ' . $fileNameOfOrdSalesData);

            $this->removeFile($removeListFile);
            $this->updateFlagExportOrderData();
            $this->updateFlagExportRmaData();

            $this->logger->info('SFTP Export Order Sales Data Success: Data entity' . implode(',', $this->entityExportOrderData));
            $this->logger->info('SFTP Export RMA Sales Data Success: Data entity' . implode(',', $this->entityExportRmaData));

            return 'SFTP Export Order Sales Data & Ord Sale Data Success: Data entity ' . implode(',', $this->entityExportOrderData) . '
                SFTP Export RMA Sales Data & Ord Sale Data Success: Data entity ' . implode(',', $this->entityExportRmaData);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Export Order Sales Data has something wrong. Message: [%s]', $e->getMessage()));
            return $e->getMessage();
        }

        $logger->info('==========================Return Sales Data Log End============================');
    }

    /**
     * @return void
     */
    public function updateFlagExportOrderData(): void
    {
        foreach ($this->entityExportOrderData as $entityId) {
            try {
                $shipment = $this->shipmentRepository->get($entityId);
                $ext = $shipment->getExtensionAttributes();
                $ext->setExportSaleDataFlag(self::EXPORTED);
                $shipment->setExtensionAttributes($ext);
                $this->shipmentRepository->save($shipment);
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                continue;
            }
        }
    }

    /**
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function updateFlagExportRmaData()
    {
        foreach ($this->entityExportRmaData as $entityId) {
            $model = $this->rmaRepository->getById($entityId);
            $model->setData('export_sale_data_flag', self::EXPORTED);
            $this->rmaRepository->save($model);
        }
    }

    public function getStoreId()
    {
        return $this->scopeConfig->getValue('sftp_korea/sftp_korea_config/store_code');
    }

    /**
     * @param $salesDataStr
     * @param $type
     * @return array
     */
    public function convertXmlFileToArray($salesDataStr, $type): array
    {
        $salesData = [];
        foreach (scandir('./' . self::EXPORT_PATH) as $filename) {
            if (str_contains($filename, $salesDataStr)) {
                $result = $this->parser->load(BP . SalesData::EXPORT_PATH . $filename)->xmlToArray();
                $saleDataArray = $result['root'][$type];
                $salesData['data'][] = $saleDataArray;
                $orderNo = $saleDataArray['order_no'] ?? $saleDataArray[0]['order_no'];
                $orderEntityId = $saleDataArray['origin_order_no'] ?? $saleDataArray[0]['origin_order_no'];
                $salesData['shipmentIds'][] = $this->removePrefix($orderNo);
                $salesData['storeId'] = $this->orderRepository->get($this->removePrefix($orderEntityId))->getStoreId();
                $salesData['fileName'][] = $filename;
            }
        }
        return $salesData;
    }
    /**
     * @param $value
     * @return string
     */
    public function removePrefix($value): string
    {
        $value = substr($value, 1);
        return ltrim($value, '0');
    }

    /**
     * @param $files
     * @return void
     */
    public function removeFile($files): void
    {
        foreach ($files as $fileName) {
            unlink(BP . SalesData::EXPORT_PATH . $fileName);
        }
    }
}
