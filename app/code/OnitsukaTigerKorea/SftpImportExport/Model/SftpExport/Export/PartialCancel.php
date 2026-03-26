<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\ExportXml;
use Magento\Framework\Registry;

class PartialCancel {

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
     * @var Registry
     */
    protected $_registry;

    /**
     * @param DirectoryList $dir
     * @param ExportXml $exportXml
     * @param ManagerInterface $eventManager
     * @param ProductRepositoryInterface $productRepository
     * @param TimezoneInterface $localeDate
     * @param Registry $registry
     * @param Logger $logger
     */
    public function __construct(
        DirectoryList $dir,
        ExportXml $exportXml,
        ManagerInterface $eventManager,
        ProductRepositoryInterface $productRepository,
        TimezoneInterface $localeDate,
        Registry $registry,
        Logger $logger
    ) {
        $this->_dir = $dir;
        $this->exportXml = $exportXml;
        $this->_eventManager = $eventManager;
        $this->productRepository = $productRepository;
        $this->localeDate = $localeDate;
        $this->_registry = $registry;
        $this->logger = $logger;
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

    /**
     * @param OrderInterface $order
     * @param ShipmentInterface $shipment
     * @param array $data
     * @return OrderInterface
     */
    public function execute(OrderInterface $order, ShipmentInterface $shipment, array $data) {
        $data =  $this->prepareData($order, $shipment, $data);
        $timeZoneDatetimeString = $this->exportXml->getTimeZoneDatetimeString('YmdHisv', $order->getStoreId());
        $fileName = 'OKR_Cancel_'.$timeZoneDatetimeString.'.xml';
        $this->setFileName($fileName);
        $rootDir = $this->_dir->getRoot();

        if (!file_exists($rootDir .self::PATH_EXPORT_CANCEL)) {
            mkdir($rootDir . self::PATH_EXPORT_CANCEL, 0777, true);
        }

        $path = $rootDir . self::PATH_EXPORT_CANCEL;
        $this->exportXml->exportToFileXml($data, $path . $this->getFileName());
        $this->logger->info('exported : ' . $this->getFileName());
        $order->setCancelXmlSynced($order->getCancelXmlSynced()+1);

        // event send mail cancel order
        $this->_registry->register('is_partial_cancel', true);
        $this->_eventManager->dispatch('order_cancel_after', ['order' => $order]);
        return $order;
    }


    protected function prepareData(OrderInterface $order, ShipmentInterface $shipment, array $data){
        $itemCancel = $data['cancel_items'];
        /** @var Order $order */
        $dataExport['Cancel'] = array();
        $index = 1; $totalItemsDiscountAmount = 0;
        $totalItems = count($order->getItems())/2;
        foreach($order->getItems() as $orderItem){
            if ($orderItem->getProductType() === 'configurable') {

                $itemCancelQty = (int)($itemCancel[$orderItem->getItemId()] ?? 0);
                if ($itemCancelQty == 0) {
                    continue;
                }

                $product = $this->productRepository->get($orderItem->getSku(),false, $orderItem->getStoreId(), true);
                $order_cancel_date = $this->localeDate->scopeDate($orderItem->getStoreId())->format('Y-m-d');
                // calculation discount amount for last item
                $discountAmount = $this->formatNumber($orderItem->getDiscountAmount());
                if ($index < $totalItems) {
                    $totalItemsDiscountAmount += $this->formatNumber($orderItem->getDiscountAmount());
                }
                if ($index == $totalItems) {
                    $discountAmount = $this->formatNumber(abs((float)$order->getDiscountAmount())) - $totalItemsDiscountAmount;
                }

                $emoney = 0;
                $usedPoint = $orderItem->getUsedPoint();
                $discountAmount = $discountAmount*$itemCancelQty/$orderItem->getQtyOrdered();
                if ($usedPoint > 0) {
                    $usedPoint = $usedPoint*$itemCancelQty/$orderItem->getQtyOrdered();
                    $discountAmount = $discountAmount - $usedPoint;
                    $discountAmount = $this->formatNumber(abs((float)$discountAmount));
                    $emoney = $this->formatNumber($usedPoint);
                }

                $dataItem = array(
                    'order_no' =>  $this->exportXml->addPrefix($shipment->getEntityId(), ExportXml::PREFIX_SHIPMENT),
                    'origin_order_no' => $this->exportXml->addPrefix($order->getEntityId(), ExportXml::PREFIX_ORDER),
                    'product_sku' => $product->getSkuWms(),
                    'product_qty' => $itemCancelQty,
                    'product_unit_price' => $this->formatNumber($orderItem->getPriceInclTax()),
                    'product_amt' => $this->formatNumber($itemCancelQty * $orderItem->getPriceInclTax()),
                    'emoney' => $emoney,
                    'coupon_sale' => $discountAmount,
                    'order_cancel_date' => $order_cancel_date,
                    'remark' => ''
                );
                $dataExport['Cancel'][] = $dataItem;
                $index++;
            }
        }

        return $dataExport;
    }

    /**
     * format number
     * @param $number
     * @return float
     */
    private function formatNumber($number): float
    {
        return round((float)$number,0,PHP_ROUND_HALF_UP);
    }
}