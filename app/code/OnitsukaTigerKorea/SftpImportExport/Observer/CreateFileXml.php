<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\SftpImportExport\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTigerKorea\SftpImportExport\Helper\Data;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export\SalesData;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\ExportXml;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\PrepareData;
use Magento\Sales\Model\Order\Shipment;

class CreateFileXml implements ObserverInterface
{
    const EXPORT_PATH = '/var/shared/sftp/export/shipment/prepare/salesdata/';

    /**
     * @var PrepareData
     */
    private PrepareData $prepareData;

    /**
     * @var DirectoryList
     */
    private DirectoryList $dir;

    /**
     * @var ExportXml
     */
    private ExportXml $exportXml;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var SalesData
     */
    protected SalesData $salesData;

    /**
     * @var Data
     */
    protected Data $helperData;

    /**
     * @param PrepareData $prepareData
     * @param DirectoryList $dir
     * @param ExportXml $exportXml
     * @param Logger $logger
     * @param SalesData $salesData
     * @param Data $data
     */
    public function __construct(
        PrepareData   $prepareData,
        DirectoryList $dir,
        ExportXml     $exportXml,
        Logger        $logger,
        SalesData     $salesData,
        Data          $helperData
    )
    {
        $this->prepareData = $prepareData;
        $this->dir = $dir;
        $this->exportXml = $exportXml;
        $this->logger = $logger;
        $this->salesData = $salesData;
        $this->helperData = $helperData;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if ($shipment->getOrigData('entity_id') &&
            !$this->helperData->getGeneralConfig('enabled', $shipment->getStoreId()))
        {
            return $this;
        }

        $salesData['Sales_Data'][] = $this->prepareData->getOrderSalesData($shipment);
        $ordSalesData['Ord_Sales_Data'][] = $this->prepareData->getOrderOrdSalesData($shipment);

        $rootDir = $this->dir->getRoot();
        try {
            if (!file_exists($rootDir . self::EXPORT_PATH)) {
                mkdir($rootDir . self::EXPORT_PATH, 0777, true);
            }

            $path = $rootDir . self::EXPORT_PATH;
            $fileNameOfSalesData = 'OKR_SalesD_' . $shipment->getEntityId() . '.xml';
            $this->exportXml->exportToFileXml($salesData, $path . $fileNameOfSalesData);
            $this->logger->info('exported : ' . $fileNameOfSalesData);

            $fileNameOfOrdSalesData = 'OKR_SalesRp_' . $shipment->getEntityId() . '.xml';
            $this->exportXml->exportToFileXml($ordSalesData, $path . $fileNameOfOrdSalesData);
            $this->logger->info('exported : ' . $fileNameOfOrdSalesData);

            $this->logger->info('Export Sales Data & Ord Sale Data Success:: Data entity' . $shipment->getEntityId());

        } catch (\Exception $e) {
            $this->logger->error(sprintf('Export Order Sales Data has something wrong. Message: [%s]', $e->getMessage()));
            return $e->getMessage();
        }
        return $this;
    }
}
