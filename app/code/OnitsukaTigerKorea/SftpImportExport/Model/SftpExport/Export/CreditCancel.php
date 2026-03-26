<?php
/** phpcs:ignoreFile */
declare(strict_types=1);
namespace OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\ExportXml;

class CreditCancel
{
    const PATH_EXPORT_CANCEL = '/var/shared/sftp/export/cancel/';

    protected $fileName;

    /**
     * @var ExportXml
     */
    protected $exportXml;

    /**
     * Application Event Dispatcher
     *
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var DirectoryList
     */
    protected $_dir;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;


    /**
     * Cancel constructor.
     * @param DirectoryList $dir
     * @param ExportXml $exportXml
     * @param ManagerInterface $eventManager
     * @param ProductRepositoryInterface $productRepository
     * @param TimezoneInterface $localeDate
     * @param Logger $logger
     */
    public function __construct(
        DirectoryList $dir,
        ExportXml $exportXml,
        ManagerInterface $eventManager,
        ProductRepositoryInterface $productRepository,
        TimezoneInterface $localeDate,
        Logger $logger,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->_dir = $dir;
        $this->exportXml = $exportXml;
        $this->_eventManager = $eventManager;
        $this->productRepository = $productRepository;
        $this->localeDate = $localeDate;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param mixed $fileName
     */
    public function setFileName($fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    public function execute($shipment)
    {
        $data =  $this->prepareData($shipment);
        $timeZoneDatetimeString = $this->exportXml->getTimeZoneDatetimeString('YmdHisv', $shipment->getStoreId());
        $fileName = 'OKR_Cancel_' . $timeZoneDatetimeString . '.xml';
        $this->setFileName($fileName);
        $rootDir = $this->_dir->getRoot();

        if (!file_exists($rootDir . self::PATH_EXPORT_CANCEL)) {
            mkdir($rootDir . self::PATH_EXPORT_CANCEL, 0777, true);
        }

        $path = $rootDir . self::PATH_EXPORT_CANCEL;
        $this->exportXml->exportToFileXml($data, $path . $this->getFileName());
        $this->logger->info('exported : ' . $this->getFileName());
        // event send mail cancel order
        // $this->_eventManager->dispatch('order_cancel_after', ['order' => $shipment]);
    }

    /**
     * @param ShipmentInterface $shipment
     * @return array
     */
    protected function prepareData($order)
    {
        /** @var Order $order */

        $order = $this->orderRepository->get($order->getOrderId());
        $data['Cancel'] = [];
        $index = 1;
        $totalItemsDiscountAmount = 0;
        $totalItems = count($order->getItems())/2;
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getProductType() === 'configurable') {
                $product = $this->productRepository->get($orderItem->getSku());
                $order_cancel_date = $this->localeDate->scopeDate($orderItem->getStoreId())->format('Y-m-d');
                $discountAmount = $this->formatNumber($orderItem->getDiscountAmount());
                if ($index < $totalItems) {
                    $totalItemsDiscountAmount += $this->formatNumber($orderItem->getDiscountAmount());
                }
                if ($index == $totalItems) {
                    $discountAmount = $this->formatNumber(abs((float)$order->getDiscountAmount())) - $totalItemsDiscountAmount;
                }
                $extNumber = explode(',', $order->getOrderXmlId());
                if ($order->getOrderXmlId() == '') {
                    $order = $this->orderRepository->get($order->getOrderId());
                    $extNumber = explode(',', $order->getOrderXmlId());
                }
               
                $dataItem = [
                    'order_no' => isset($extNumber[1]) ? trim($extNumber[1]) : $order->getIncrementId(),
                    'origin_order_no' => $this->exportXml->addPrefix($orderItem->getOrderId(), ExportXml::PREFIX_ORDER),
                    'product_sku' => $product->getSkuWms(),
                    'product_qty' => (int) $orderItem->getQtyOrdered(),
                    'product_unit_price' => $this->formatNumber($orderItem->getPriceInclTax()),
                    'product_amt' => $this->formatNumber($orderItem->getRowTotalInclTax()),
                    'emoney' => 0,
                    'coupon_sale' => $discountAmount,
                    'order_cancel_date' => $order_cancel_date,
                    'remark' => ''
                ];
                $data['Cancel'][] = $dataItem;
                $index++;
            }
        }
        return $data;
    }

    /**
     * format number
     * @param $number
     * @return float
     */
    private function formatNumber($number): float
    {
        return round((float)$number, 0, PHP_ROUND_HALF_UP);
    }
}
