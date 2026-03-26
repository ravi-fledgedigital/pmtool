<?php
//phpcs:ignoreFile
declare(strict_types=1);
namespace OnitsukaTigerKorea\SftpImportExport\Model\SftpImport;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Xml\Parser;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTigerKorea\Sales\Model\StockKorean;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export\SalesData;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\ExportXml;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpImport;
use OnitsukaTigerKorea\SftpImportExport\Observer\CreateFileXml;

/**
 * Class ImportRelease | need to update shipment (order) status to Shipped.
 * @package OnitsukaTigerKorea\SftpImportExport\Model\SftpImport
 */
class ImportRelease extends SftpImport
{
    /**
     * @var OrderStatus
     */
    protected OrderStatus $orderStatusModel;
    /**
     * @var StockKorean
     */
    protected StockKorean $stockKorean;
    /**
     * @var Logger
     */
    protected Logger $logger;
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;
    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    protected OrderStatusHistoryRepositoryInterface $orderStatusRepository;
    /**
     * @var File
     */
    protected File $filesystemIo;
    /**
     * @var ExportXml
     */
    protected ExportXml $exportXml;
    /**
     * @var Parser
     */
    protected Parser $parser;
    /**
     * @var DirectoryList
     */
    protected DirectoryList $dir;
    protected $releaseDate;
    /**
     * ImportShipping constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderStatusHistoryRepositoryInterface $orderStatusRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param OrderStatus $orderStatusModel
     * @param StockKorean $stockKorean
     * @param Logger $logger
     * @param File $filesystemIo
     * @param ExportXml $exportXml
     * @param Parser $parser
     * @param DirectoryList $dir
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepositoryInterface $shipmentRepository,
        OrderStatus $orderStatusModel,
        StockKorean $stockKorean,
        Logger $logger,
        File $filesystemIo,
        ExportXml $exportXml,
        Parser $parser,
        DirectoryList $dir
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->orderStatusModel = $orderStatusModel;
        $this->stockKorean = $stockKorean;
        $this->logger = $logger;
        $this->filesystemIo = $filesystemIo;
        $this->exportXml = $exportXml;
        $this->parser = $parser;
        $this->dir = $dir;
        parent::__construct($searchCriteriaBuilder, $shipmentRepository);
    }
    /**
     * Execute Function
     *
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/Release/ImportRelease.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $result = ['Shipment' => []];
        foreach ($data as $shipment) {
            $shipmentId = $this->removePrefix($shipment['order_no']);
            $orderId = $this->removePrefix($shipment['origin_order_no']);
            $productQty = $shipment['product_qty'];
            if (!$this->validateProductQtyNegative($productQty)) {
                $result['Shipment'][$shipmentId] = [
                    'status' => 'fail',
                    'message' => sprintf('Shipment has product with qty [%s] is negative', $productQty)
                ];
                $logger->info('Validate Product Qty Negative: ' . print_r($result, true));
                $this->logger->debug(sprintf('Shipment [%s] has product with qty [%s] is negative', $shipmentId, $productQty));
                continue;
            }

            $this->setReleaseDate($shipment['act_release_date']);

            try {
                $shipment = $this->getShipmentByIdWithSearchCriteria(trim($shipmentId));
                $order = $this->orderRepository->get(trim($orderId));
                $logger->info("Shipment ID: " . $shipmentId);
                $logger->info("Order ID: " . $order->getId());
                if (is_null($shipment)) {
                    $logger->info("Shipment not found. Update order status to shipped.");
                    $order->setStatus(ShipmentStatus::STATUS_SHIPPED);
                    $order->addCommentToStatusHistory(sprintf('Message: I/F Release - Updated order status to Shipped'));
                    $this->orderRepository->save($order);
                } else {
                    if ($this->validateShipment($shipment, [ShipmentStatus::STATUS_PROCESSING])) {
                        $logger->info("Shipment validated. Update shipment status start");
                        $result['Shipment'][$shipmentId] = $this->updateShipmentStatusToShipped($shipment);
                        $logger->info("Shipment validated. Update shipment status end. Shipment ID: " . $shipmentId);
                    } else {
                        $logger->info("Shipment validation failed");
                        $result['Shipment'][$shipmentId] = [
                            'status' => 'fail',
                            'message' => sprintf('Message: shipment id [%s] is not status[%s]', $shipment->getIncrementId(), implode(', ', [ShipmentStatus::STATUS_PROCESSING]))
                        ];
                        $logger->info("Result: " . print_r($result, true));
                        $this->logger->debug(sprintf('Message: shipment id [%s] is not status[%s]', $shipment->getEntityId(), implode(', ', [ShipmentStatus::STATUS_PROCESSING])));
                    }
                }

                $salesDataFileName = SalesData::TYPE_NAME_SALE_DATA . '_' . $shipmentId . '.xml';
                $ordSalesDataFileName = SalesData::TYPE_NAME_SALE_DATA_RP . '_' . $shipmentId . '.xml';
                $logger->info('SalesDataFileName: ' . $salesDataFileName);
                $logger->info('OrdSalesDataFileName: ' . $ordSalesDataFileName);
                $this->updateAndMoveSaleDataXml($salesDataFileName, SalesData::PATH_SALE_DATA, $shipmentId);
                $this->updateAndMoveSaleDataXml($ordSalesDataFileName, SalesData::PATH_ORD_SALE_DATA, $shipmentId);
            } catch (\Exception $e) {
                $result['Shipment'][$shipmentId] = [
                    'status' => 'fail',
                    'message' => 'Message:' . $e->getMessage()
                ];
                $logger->info("Shipment ID: " . $shipmentId);
                $logger->info("Result: " . print_r($result, true));
                $this->logger->debug(sprintf('shipment id [%s] has something wrong: [%s]', $shipmentId, $e->getMessage()));
            }
        }
        $this->recheckedShipmentStatus($data);
        return $result;
    }

    protected function recheckedShipmentStatus($data)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/Release/ImportReleaseRechecked.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $result = ['Shipment' => []];
        foreach ($data as $shipment) {
            $shipmentId = $this->removePrefix($shipment['order_no']);
            $orderId = $this->removePrefix($shipment['origin_order_no']);
            $productQty = $shipment['product_qty'];
            if (!$this->validateProductQtyNegative($productQty)) {
                $result['Shipment'][$shipmentId] = [
                    'status' => 'fail',
                    'message' => sprintf('Shipment has product with qty [%s] is negative', $productQty)
                ];
                $logger->info('Recheck Validate Product Qty Negative: ' . print_r($result, true));
                $this->logger->debug(sprintf('Shipment [%s] has product with qty [%s] is negative', $shipmentId, $productQty));
                continue;
            }

            $this->setReleaseDate($shipment['act_release_date']);

            try {
                $shipment = $this->getShipmentByIdWithSearchCriteria(trim($shipmentId));
                if ($shipment->getExtensionAttributes()->getStatus() == OrderStatus::STATUS_SHIPPED) {
                    $logger->info("Shipment status already updated to shipped. Shipment ID: " . $shipmentId);
                    continue;
                }
                $order = $this->orderRepository->get(trim($orderId));
                $logger->info("Recheck Shipment ID: " . $shipmentId);
                $logger->info("Recheck Order ID: " . $order->getId());

                if (is_null($shipment)) {
                    $logger->info("Recheck Shipment not found. Update order status to shipped.");
                    $order->setStatus(ShipmentStatus::STATUS_SHIPPED);
                    $order->addCommentToStatusHistory(sprintf('Message: I/F Release - Updated order status to Shipped'));
                    $this->orderRepository->save($order);
                } else {
                    if ($this->validateShipment($shipment, [ShipmentStatus::STATUS_PROCESSING])) {
                        $logger->info("Recheck Shipment validated. Update shipment status start");
                        $result['Shipment'][$shipmentId] = $this->updateShipmentStatusToShipped($shipment);
                        $logger->info("Recheck Shipment validated. Update shipment status end. Shipment ID: " . $shipmentId);
                    } else {
                        $logger->info("Recheck Shipment validation failed");
                        $result['Shipment'][$shipmentId] = [
                            'status' => 'fail',
                            'message' => sprintf('Message: shipment id [%s] is not status[%s]', $shipment->getIncrementId(), implode(', ', [ShipmentStatus::STATUS_PROCESSING]))
                        ];
                        $logger->info("Recheck Result: " . print_r($result, true));
                        $this->logger->debug(sprintf('Message: shipment id [%s] is not status[%s]', $shipment->getEntityId(), implode(', ', [ShipmentStatus::STATUS_PROCESSING])));
                    }
                }

                $salesDataFileName = SalesData::TYPE_NAME_SALE_DATA . '_' . $shipmentId . '.xml';
                $ordSalesDataFileName = SalesData::TYPE_NAME_SALE_DATA_RP . '_' . $shipmentId . '.xml';
                $logger->info('Recheck SalesDataFileName: ' . $salesDataFileName);
                $logger->info('Recheck OrdSalesDataFileName: ' . $ordSalesDataFileName);
                $this->updateAndMoveSaleDataXml($salesDataFileName, SalesData::PATH_SALE_DATA, $shipmentId);
                $this->updateAndMoveSaleDataXml($ordSalesDataFileName, SalesData::PATH_ORD_SALE_DATA, $shipmentId);
            } catch (\Exception $e) {
                $result['Shipment'][$shipmentId] = [
                    'status' => 'fail',
                    'message' => 'Message:' . $e->getMessage()
                ];
                $logger->info("Recheck Shipment ID: " . $shipmentId);
                $logger->info("Recheck Result: " . print_r($result, true));
                $this->logger->debug(sprintf('shipment id [%s] has something wrong: [%s]', $shipmentId, $e->getMessage()));
            }
        }
    }

    /**
     * Update Shipment Status To Shipped
     *
     * @param ShipmentInterface $shipment
     * @return array
     * @throws Exception
     */
    protected function updateShipmentStatusToShipped(ShipmentInterface $shipment): array
    {
        $incrementId = $shipment->getIncrementId();
        $this->stockKorean->orderShipped($incrementId);

        $ext = $shipment->getExtensionAttributes();
        $ext->setStatus(OrderStatus::STATUS_SHIPPED);
        $ext->setActReleaseDate($this->getReleaseDate());
        $shipment->setExtensionAttributes($ext);

        $this->shipmentRepository->save($shipment);

        $this->orderStatusModel->setOrderStatus($shipment->getOrder());

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/Release/ImportReleaseUpdateShipmentStatusToShipped.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Shipment Increment ID: ' . $incrementId);
        $logger->info('Shipment Status: ' . $shipment->getExtensionAttributes()->getStatus());

        $this->logger->debug(sprintf('Message: Update shipment id [%s] status to shipped successfully', $shipment->getEntityId()));

        return [
            'status' => 'success',
            'message' => 'Message: Update shipment status to shipped successfully'
        ];
    }
    /**
     * Update And Move Sale Data Xml
     *
     * @param $fileName
     * @param $type
     * @return void
     */
    protected function updateAndMoveSaleDataXml($fileName, $type, $shipmentId): void
    {
        $rootDir = $this->dir->getRoot();
        $filePath = $rootDir . CreateFileXml::EXPORT_PATH . $fileName;
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/Release/ImportReleaseUpdateAndMoveSaleDataXml.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Filename: ' . $fileName);
        $logger->info('Filepath: ' . $filePath);
        $logger->info('Type: ' . $type);
        if (file_exists($filePath)) {
            $result = $this->parser->load($filePath)->xmlToArray();
            $saleDataArray = $result['root'][$type];
            if (array_key_exists('order_no', $saleDataArray)) {
                $saleDataArray['act_date'] = $this->getReleaseDate();
            } elseif (array_key_exists('order_no', $saleDataArray[0])) {
                foreach ($saleDataArray as &$item) {
                    $item['act_date'] = $this->getReleaseDate();
                }
            }
            $newData[$type][] = $saleDataArray;
            $path = $rootDir . SalesData::EXPORT_PATH;
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            $this->exportXml->exportToFileXml($newData, $path . $fileName);
            $shipment = $this->getShipmentByIdWithSearchCriteria(trim($shipmentId));
            $logger->info('Shipment ID: ' . $shipmentId);
            $logger->info('Shipment Data: ' . $shipment->getEntityId());
            if ($shipment && $shipment->getEntityId()) {
                $logger->info('Shipment Status: ' . $shipment->getExtensionAttributes()->getStatus());
                if ($shipment->getExtensionAttributes()->getStatus() == OrderStatus::STATUS_SHIPPED) {
                    $logger->info('File unlink completed');
                    unlink($filePath);
                }
            }
        }
    }
    /**
     * @param $releaseDate
     */
    public function setReleaseDate($releaseDate)
    {
        $this->releaseDate = date('Ymd', strtotime($releaseDate));
    }
    /**
     * @return mixed
     */
    public function getReleaseDate(): mixed
    {
        return $this->releaseDate;
    }
}
