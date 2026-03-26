<?php
namespace OnitsukaTigerKorea\SftpImportExport\Model\Export\Adapter;

use Firebear\ImportExport\Model\Output\Xslt;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\OrderRepository;
use OnitsukaTiger\NetsuiteOrderSync\Helper\Data;
use OnitsukaTiger\OrderStatus\Model\Shipment;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\ExportXml;
use Psr\Log\LoggerInterface;
use XMLWriter;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\Filter\StripNewlines;

class Xml extends \Firebear\ImportExport\Model\Export\Adapter\Xml
{
    const FIELD_RECURSIZE = ['item', 'address'];

    public $w = true;
    protected $index = [];
    protected $storeId = \OnitsukaTiger\Store\Model\Store::KO_KR;

    /**
     * Order data
     *
     * @var []
     */
    protected $orderData = [];

    protected $orderDataSort = [];

    protected $dataAdd = [];

    protected $order = [];

    /**
     * @var OrderRepository
     */
    protected $orderRespository;

    /**
     * @var Data
     */
    protected $_helperNetsuite;

    /**
     * @var Shipment
     */
    protected $shipmentModel;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \OnitsukaTigerKorea\SftpImportExport\Helper\Data
     */
    protected $helperSftpKorea;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var ExportXml
     */
    protected $exportXml;

    protected $orderRepository;

    /**
     * Xml constructor.
     * @param ExportXml $exportXml
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param TimezoneInterface $localeDate
     * @param ProductRepositoryInterface $productRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param \OnitsukaTigerKorea\SftpImportExport\Helper\Data $helperSftpKorea
     * @param Shipment $shipmentModel
     * @param Data $_helperNetsuite
     * @param OrderRepository $orderRepository
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param XMLWriter $writer
     * @param Xslt $xslt
     * @param null $destination
     * @param string $destinationDirectoryCode
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        ExportXml $exportXml,
        OrderItemRepositoryInterface $orderItemRepository,
        TimezoneInterface $localeDate,
        ProductRepositoryInterface $productRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        \OnitsukaTigerKorea\SftpImportExport\Helper\Data $helperSftpKorea,
        Shipment $shipmentModel,
        Data $_helperNetsuite,
        OrderRepository $orderRepository,
        Filesystem $filesystem,
        LoggerInterface $logger,
        XMLWriter $writer,
        Xslt $xslt,
        $destination = null,
        $destinationDirectoryCode = DirectoryList::VAR_DIR,
        array $data = []
    ) {
        $this->exportXml = $exportXml;
        $this->orderItemRepository = $orderItemRepository;
        $this->localeDate = $localeDate;
        $this->productRepository = $productRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->helperSftpKorea = $helperSftpKorea;
        $this->shipmentModel = $shipmentModel;
        $this->_helperNetsuite = $_helperNetsuite;
        $this->orderRespository = $orderRepository;
        parent::__construct($filesystem, $logger, $writer, $xslt, $destination, $destinationDirectoryCode, $data);
    }

    /**
     * @param array $rowData
     * @return $this|Xml
     */
    public function writeRow(array $rowData)
    {
        if (!empty($rowData)) {
            // If module SFTPKorea is not available then back parent source code
            $order = $this->orderRespository->get($rowData['order_no']);
            $this->storeId = $order->getStoreId();

            if ($order->getOrderSynced()) {
                return $this;
            }
            if (!$this->helperSftpKorea->getGeneralConfig('enable', $order->getStoreId())) {
                return parent::writeRow($rowData);
            }

            $this->splitOrderBasedOnShipment($rowData);
            foreach ($this->orderData as $shipmentId => $rowData) {
                $this->index = [];
                if (isset($rowData['item']) && count($rowData['item'])) {
                    for ($i = 0; $i < count($rowData['item']); $i++) {
                        foreach ($rowData as $key => $value) {
                            $this->w = true;
                            if (is_array($value)) {
                                if (in_array($key, self::FIELD_RECURSIZE)) {
                                    $this->recursiveAdd($key, $value);
                                }
                            } elseif (is_string($key)) {
                                if ($key=='coupon_sale_total') {
                                    $this->dataAdd[$key] = abs((float)$value);
                                } else {
                                    $this->dataAdd[$key] = $value;
                                }
                            }
                        }
                        $this->dataAdd = $this->sortStructure($this->dataAdd);
                        $this->orderDataSort[$i] = $this->dataAdd;
                        unset($this->dataAdd);
                    }
                    unset($this->order);
                    $this->order[$shipmentId] = $this->orderDataSort;
                    unset($this->orderDataSort);
                }
            }
            if (!empty($this->orderData)) {
                $this->writeXml($this->order);
                $order->setOrderSynced(1);
                $order->addCommentToStatusHistory('Synced order to SFTP');
                $this->orderRespository->save($order);
            }
        }
        return $this;
    }

    /**
     * @return \Firebear\ImportExport\Model\Export\Adapter\Xml
     */
    protected function _init()
    {
        $this->writer->openURI('php://output');
        $this->writer->openMemory();
        $this->writer->startDocument("1.0", "UTF-8");
        $this->writer->setIndent(1);
        $this->writer->startElement("root");

        return $this;
    }

    /**
     * @param $key
     * @param array $data
     */
    protected function recursiveAdd($key, array $data)
    {
        if (!$this->helperSftpKorea->getGeneralConfig('enable', $this->storeId)) {
            parent::recursiveAdd($key, $data);
        }

        if (!empty($data)) {
            foreach ($data as $ki => $values) {
                if ($key === 'address' && is_numeric($ki) && $ki % 2 != 0) {
                    continue;
                }
                if (is_array($values)) {
                    if ($this->w == false) {
                        break;
                    }
                    if (is_numeric($ki)) {
                        if (in_array($ki, $this->index) && $key != 'address') {
                            continue;
                        }
                        $this->index[] = $ki;
                    }
                    $this->recursiveAdd($ki, $values);
                } else {
                    if ($ki == 'item_id' || $ki == 'entity_id') {
                        continue;
                    }
                    if ($ki == 'coupon_sale' || $ki == 'coupon_sale_total') {
                        $this->dataAdd[$ki] = abs((float)$values);
                    } elseif ($ki == 'product_qty' || $ki == 'product_unit_price') {
                        $this->dataAdd[$ki] = (int)($values);
                    } else {
                        $this->dataAdd[$ki] = $values;
                    }

                    $this->w = false;
                }
            }
        }
    }

    /**
     * @param array $rowData
     */
    protected function splitOrderBasedOnShipment(array $rowData)
    {
        if (isset($rowData['order_no'])) {
            $this->orderData = [];
            $order = $this->orderRespository->get($rowData['order_no']);
            if ($this->helperSftpKorea->enableOrderSyncWithMultiShipmentSFTP($order->getStoreId())) {
                // get list shipment in order
                $shipments = $this->shipmentModel->getShipmentsByOrderId((int)$order->getEntityId());
                if (count($shipments)) {
                    foreach ($shipments as $shipmentId => $shipment) {
                        $rowDataTmp = $rowData;
                        // get all items in shipment
                        $shipmentItems = $shipment->getAllItems();
                        $data = [];
                        $index = 1;
                        $totalItemsDiscountAmount = 0;
                        foreach ($shipmentItems as $key => $shipmentItem) {
                            foreach ($rowDataTmp['item'] as $item) {
                                if ($shipmentItem->getOrderItemId() == $item['item']['item_id']) {
                                    $totalItems = count($shipmentItems);
                                    $product = $this->productRepository->get($shipmentItem->getSku());
                                    $orderItem = $this->orderItemRepository->get($shipmentItem->getOrderItemId());
                                    $item['item']['product_sku'] = $product->getSkuWms();
                                    $item['item']['product_qty'] = (int) $item['item']['product_qty'];
                                    // calculation discount amount for last item
                                    $item['item']['coupon_sale'] = $this->formatNumber($item['item']['coupon_sale']);
                                    if ($index < $totalItems) {
                                        $totalItemsDiscountAmount += $this->formatNumber($item['item']['coupon_sale']);
                                    }

                                    if ($index == $totalItems) {
                                        $item['item']['coupon_sale'] = $this->formatNumber(abs((float)$order->getDiscountAmount())) - $totalItemsDiscountAmount;
                                    }
                                    $item['item']['product_unit_price'] = $this->formatNumber($item['item']['product_unit_price']);
                                    $item['item']['order_line_no'] = ++$key;
                                    $item['item']['product_amt'] = $this->formatNumber($this->orderItemRepository->get($shipmentItem->getOrderItemId())->getRowTotalInclTax());
                                    $data[] = $item;
                                }
                            }
                            $index++;
                        }

                        foreach ($rowDataTmp['address'] as $key => $address) {
                            $rowDataTmp['address'][$key]['item']['order_user_name'] = $this->helperSftpKorea->formatOrderUserName($this->removeSpecialCharaterFromString($order->getBillingAddress()->getFirstname()));
                            //$rowDataTmp['address'][$key]['item']['order_user_name'] = $this->helperSftpKorea->formatOrderUserName($order->getBillingAddress()->getFirstname());
                            $rowDataTmp['address'][$key]['item']['order_cellphone'] = $order->getBillingAddress()->getTelephone();
                            $rowDataTmp['address'][$key]['item']['order_email'] = $order->getBillingAddress()->getEmail();
                            $rowDataTmp['address'][$key]['item']['recipient_address_type'] = 'street';
                            $streets = $order->getShippingAddress()->getStreet();
                            $rowDataTmp['address'][$key]['item']['recipient_address_street'] = $this->removeSpecialCharaterFromString($streets[0]);
                            //$rowDataTmp['address'][$key]['item']['recipient_address_street'] = $streets[0];
                            if (count($streets) > 1) {
                                $rowDataTmp['address'][$key]['item']['recipient_address_detail'] = $this->removeSpecialCharaterFromString($streets[1]);
                                //$rowDataTmp['address'][$key]['item']['recipient_address_detail'] = $streets[1];
                            } else {
                                $rowDataTmp['address'][$key]['item']['recipient_address_detail'] = '';
                            }
                        }

                        $rowDataTmp['order_no'] = $this->exportXml->addPrefix($shipmentId, ExportXml::PREFIX_SHIPMENT);
                        $rowDataTmp['origin_order_no'] = $this->exportXml->addPrefix($rowDataTmp['origin_order_no'], ExportXml::PREFIX_ORDER);
                        $rowDataTmp['item'] = $data;

                        if(isset($order['gift_packaging']) && $order['gift_packaging'] == 1) {
                            $rowDataTmp['gift_type'] = 1;
                        } else {
                            $rowDataTmp['gift_type'] = 2;
                        }
                        /*if (is_null($rowDataTmp['gift_wrap_price']) || $rowDataTmp['gift_wrap_price'] == '') {
                            $rowDataTmp['gift_type'] = 2;
                        } else {
                            $rowDataTmp['gift_type'] = 1;
                        }*/
                        $rowDataTmp['emoney'] = 0.00;
                        $rowDataTmp['emoney_total'] = 0.00;
                        $rowDataTmp['order_date'] = $this->localeDate->date(
                            $this->localeDate->formatDateTime(
                                $rowDataTmp['order_date'],
                                \IntlDateFormatter::SHORT,
                                \IntlDateFormatter::SHORT,
                                null,
                                $this->localeDate->getConfigTimezone('store', $shipment->getStoreId())
                            )
                        )->format('Y-m-d H:i:s');
                        unset($rowDataTmp['order_line_no']);
                        unset($rowDataTmp['product_amt']);

                        $rowDataTmp = $this->formatData($rowDataTmp);
                        $this->orderData[$shipmentId] =  $rowDataTmp;
                    }
                }

                return  $this->orderData;
            }
        }
    }

    protected function formatData(array $data)
    {
        // format number
        foreach ($data as $key => $val) {
            if (
                (
                    $key == 'coupon_sale_total' ||
                    $key == 'delivery_charge' ||
                    $key == 'product_amt' ||
                    $key == 'gift_wrap_price' ||
                    $key == 'settle_price' ||
                    $key == 'emoney' ||
                    $key == 'emoney_total'
                ) && ($val != '' || !is_null($val))
            ) {
                $data[$key] = $this->formatNumber($val);
            }
        }

        return $data;
    }

    /**
     * @param array $order
     * @return array
     */
    protected function sortStructure(array $order): array
    {
        return [
            'order_no' => (isset($order['order_no'])) ? $order['order_no'] : '',
            'origin_order_no' => (isset($order['origin_order_no'])) ? $order['origin_order_no'] : '',
            'order_line_no' => (isset($order['order_line_no'])) ? $order['order_line_no'] : '',
            'product_name' => (isset($order['product_name'])) ? $order['product_name'] : '',
            'product_sku' => (isset($order['product_sku'])) ? $order['product_sku'] : '',
            'product_qty' => (isset($order['product_qty'])) ? $order['product_qty'] : '',
            'product_unit_price' => (isset($order['product_unit_price'])) ? $order['product_unit_price'] : '',
            'product_amt' => (isset($order['product_amt'])) ? $order['product_amt'] : '',
            'delivery_charge' => (isset($order['delivery_charge'])) ? $order['delivery_charge'] : '',
            'settle_price' => (isset($order['settle_price'])) ? $order['settle_price'] : '',
            'emoney' => (isset($order['emoney'])) ? $order['emoney'] : '',
            'coupon_sale' => (isset($order['coupon_sale'])) ? $order['coupon_sale'] : '',
            'emoney_total' => (isset($order['emoney_total'])) ? $order['emoney_total'] : '',
            'coupon_sale_total' => (isset($order['coupon_sale_total'])) ? $order['coupon_sale_total'] : '',
            'store_id' => (isset($order['store_id'])) ? $order['store_id'] : '',
            'order_user_name' => (isset($order['order_user_name'])) ? $this->removeSpecialCharaterFromString($order['order_user_name']) : '',
            //'order_user_name' => (isset($order['order_user_name'])) ? $order['order_user_name'] : '',
            'order_phone' => (isset($order['order_phone'])) ? $order['order_phone'] : '',
            'order_cellphone' => (isset($order['order_cellphone'])) ? $order['order_cellphone'] : '',
            'order_email' => (isset($order['order_email'])) ? $order['order_email'] : '',
            'recipient_user_name' => (isset($order['recipient_user_name'])) ? $this->removeSpecialCharaterFromString($order['recipient_user_name']) : '',
            //'recipient_user_name' => (isset($order['recipient_user_name'])) ? $order['recipient_user_name'] : '',
            'recipient_phone' => (isset($order['recipient_phone'])) ? $order['recipient_phone'] : '',
            'recipient_cellphone' => (isset($order['recipient_cellphone'])) ? $order['recipient_cellphone'] : '',
            'recipient_zipcode' => (isset($order['recipient_zipcode'])) ? $order['recipient_zipcode'] : '',
            'recipient_address_type' => (isset($order['recipient_address_type'])) ? $order['recipient_address_type'] : '',
            'recipient_address_street' => (isset($order['recipient_address_street'])) ? $this->removeSpecialCharaterFromString($order['recipient_address_street']) : '',
            //'recipient_address_street' => (isset($order['recipient_address_street'])) ? $order['recipient_address_street'] : '',
            'recipient_address_detail' => (isset($order['recipient_address_detail'])) ? $this->removeSpecialCharaterFromString($order['recipient_address_detail']) : '',
            //'recipient_address_detail' => (isset($order['recipient_address_detail'])) ? $order['recipient_address_detail'] : '',
            'delivery_memo' => (isset($order['delivery_memo'])) ? $order['delivery_memo'] : '',
            'order_date' => (isset($order['order_date'])) ? $order['order_date'] : '',
            'gift_type' => (isset($order['gift_type'])) ? $order['gift_type'] : '',
            'gift_wrap_price' => (isset($order['gift_wrap_price'])) ? $order['gift_wrap_price'] : '',
            'gift_delivery_memo' => (isset($order['gift_delivery_memo'])) ? $order['gift_delivery_memo'] : '',
            'remark1' => (isset($order['remark1'])) ? $order['remark1'] : '',
            'remark2' => (isset($order['remark2'])) ? $order['remark2'] : '',
            'remark3' => (isset($order['remark3'])) ? $order['remark3'] : ''
        ];
    }

    /**
     * @param array $data
     */
    protected function writeXml(array $data)
    {
        foreach ($data as $shipment) {
            foreach ($shipment as $rowData) {
                if (!empty($rowData)) {
                    $this->writer->startElement('Order');
                    foreach ($rowData as $key => $value) {
                        if (is_string($key)) {
                            $this->writer->writeElement($key, $value);
                        }
                    }
                    $this->writer->endElement();
                }
            }
        }
    }

    private function removeSpecialCharaterFromString($string)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create(\OnitsukaTiger\Store\Helper\Data::class);
        return $helper->removeSpecialCharacter($string);
    }

    /**
     * @param $string
     * @return mixed|null
     */
    private function senitizeRecipientAddressDetail($string)
    {
        $filters = [
            '*' => new StringTrim(), // Trim whitespace from all fields
            '*' => new StripTags(), // Remove HTML tags from all fields
            '*' => new StripNewlines(), // Remove newlines from all fields
            'recipient_address_detail' => function ($value) { // Custom Alphanumeric Filter
                return preg_replace('/[^a-zA-Z0-9]/', '', $value);
            }, // Allow only alphanumeric characters in recipient_address_detail field
        ];
        $input = new \Magento\Framework\Filter\FilterInput($filters, [], ['recipient_address_detail' => $string]);
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/SenitizeAddress.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Recipient Detail'.$input->getEscaped('recipient_address_detail'));
        return $input->getEscaped('recipient_address_detail');
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