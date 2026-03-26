<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : thanhnv7184@co-well.com.vn
 */

namespace OnitsukaTiger\NetsuiteOrderSync\Model\Export\Adapter;

use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\InventoryShipping\Model\ResourceModel\ShipmentSource\GetSourceCodeByShipmentId;
use Magento\Sales\Model\OrderRepository;
use OnitsukaTiger\NetsuiteOrderSync\Helper\Data;
use OnitsukaTiger\NetsuiteOrderSync\Plugin\Controller\Adminhtml\Job\FilterStockLocation;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;

class Order extends \Firebear\PlatformNetsuite\Model\Export\Adapter\Order {

    /**
     * Order data
     *
     * @var []
     */
    protected $orderData;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var \OnitsukaTiger\NetsuiteOrderSync\Model\Export\Adapter\Gateway\Order
     */
    protected $gateway;

    /**
     * @var OrderRepository
     */
    protected $orderRespository;

    /**
     * @var GetSourceCodeByShipmentId
     */
    protected $getSourceCodeByShipmentId;

    /**
     * @var Data
     */
    protected $_helperNetsuite;

    /**
     * @var \OnitsukaTiger\OrderStatus\Model\Shipment
     */
    protected $shipmentModel;

    /**
     * @var StoreShipping
     */
    protected $storeShipping;

    /**
     * @param \OnitsukaTiger\OrderStatus\Model\Shipment $shipmentModel
     * @param Filesystem $filesystem
     * @param Gateway\Order $gateway
     * @param CountryFactory $countryFactory
     * @param ProductRepository $productRepository
     * @param CustomerRepository $customerRepository
     * @param null $destination
     * @param array $data
     * @param OrderRepository $orderRepository
     * @param GetSourceCodeByShipmentId $getSourceCodeByShipmentId
     * @param Data $_helperNetsuite
     * @param StoreShipping $storeShipping
     */
    public function __construct(
        \OnitsukaTiger\OrderStatus\Model\Shipment $shipmentModel,
        Filesystem $filesystem,
        \OnitsukaTiger\NetsuiteOrderSync\Model\Export\Adapter\Gateway\Order $gateway,
        CountryFactory $countryFactory,
        ProductRepository $productRepository,
        CustomerRepository $customerRepository,
        OrderRepository $orderRepository,
        GetSourceCodeByShipmentId $getSourceCodeByShipmentId,
        Data $_helperNetsuite,
        StoreShipping $storeShipping,
        $destination = null,
        array $data = []
    )
    {
        $this->shipmentModel = $shipmentModel;
        $this->_helperNetsuite = $_helperNetsuite;
        $this->getSourceCodeByShipmentId = $getSourceCodeByShipmentId;
        $this->orderRespository = $orderRepository;
        $this->gateway = $gateway;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->storeShipping = $storeShipping;
        $this->_data = $data;

        if (isset($data['behavior_data'])) {
            $data = $data['behavior_data'];
            $this->gateway->setBehaviorData($data);
            $this->_delimiter = $data['separator'] ?? $this->_delimiter;
            $this->_enclosure = $data['enclosure'] ?? $this->_enclosure;
        }

        parent::__construct(
            $filesystem,
            $gateway,
            $countryFactory,
            $productRepository,
            $customerRepository,
            $destination,
            $data
        );
    }

    /**
     * Write row data to source file.
     *
     * @param array $rowData
     * @throws \Exception
     * @return \OnitsukaTiger\NetsuiteOrderSync\Model\Export\Adapter\Order
     */
    public function writeRow(array $rowData)
    {
        $rowData = $this->ignoreShipmentDataTable($rowData);
        $this->prepareOrderData($rowData);

        // customize
        if (isset($rowData['increment_id'])
            && ((isset($this->orderData[$rowData['increment_id']])
                && !empty($this->orderData)))
        ) {
            foreach ($rowData as $attr => $value){
                if( (isset($this->orderData[$rowData['increment_id']][$attr])) || (strpos($attr,'item:') !== false)) {continue;}
                $this->orderData[$rowData['increment_id']][$attr] = isset($value)?$value:'';
            }
        }
        // endl

        if (!empty($this->orderData)) {
            reset($this->orderData);
            $incrementId = key($this->orderData);
            $itemsCount = count($this->orderData[$incrementId]['items']);

            if ((($itemsCount == $this->orderData[$incrementId]['product_total']))
                && isset($this->orderData[$incrementId]['billing_address'])
                && isset($this->orderData[$incrementId]['shipping_address'])
                && (isset($rowData['increment_id'])
                    || isset($rowData['item:product_type'])
                    || isset($rowData['address:entity_id']))
            ) {
                $order = $this->orderRespository->get($this->orderData[$incrementId]['entity_id']);
                if($order->getSubtotalInclTax() == 0) {
                    return $this;
                }
                if($this->_helperNetsuite->enableOrderSyncWithMultiShipment($order->getStoreId())) {
                    $exportShipData = [];
                    // get list shipment in order
                    $shipments = $this->shipmentModel->getShipmentsByOrderId((int)$order->getId());
                    if (!empty($this->orderData[$incrementId][FilterStockLocation::STOCK_LOCATION])) {
                        $shipments = $this->filterShipmentsByStockLocation($shipments, $this->orderData[$incrementId][FilterStockLocation::STOCK_LOCATION]);
                    }

                    if($this->_helperNetsuite->ignoreSourceStoreSyncToNetSuite($order->getStoreId())) {
                        $shipments = $this->ignoreShipmentFromStore($shipments);
                    }

                    if (count($shipments)) {
                        /** @var Shipment $shipment */
                        foreach ($shipments as $key => $shipment) {
                            $orderShipment = $this->orderData[$incrementId];
                            $sourceCode = $this->getSourceCodeByShipmentId->execute((int)$shipment->getId());
                            // get all items in shipment
                            $shipmentItems = $shipment->getAllItems();
                            $data = [];
                            $subTotalItems = 0;
                            $taxAmountItems = 0;
                            $discountAmountItem = 0;
                            foreach ($shipmentItems as $shipmentItem) {
                                foreach ($this->orderData[$incrementId]['items'] as $item) {
                                    if ($shipmentItem->getOrderItemId() == $item['dataItem']['item:item_id']) {
                                        // update item data
                                        $item['dataItem']['item:tax_amount'] = ($item['dataItem']['item:tax_amount']/$item['dataItem']['item:qty_ordered']) * $shipmentItem->getQty();
                                        $item['quantity'] = $shipmentItem->getQty();
                                        $item['amount'] = $item['dataItem']['item:price_incl_tax'] * $shipmentItem->getQty();
                                        $item['discount_amount'] = ($item['discount_amount'] / $item['dataItem']['item:qty_ordered']) * $shipmentItem->getQty();
                                        $item['dataItem']['item:qty_ordered'] = $shipmentItem->getQty();
                                        $item['dataItem']['item:row_total'] = $item['dataItem']['item:price'] * $shipmentItem->getQty();
                                        $item['dataItem']['item:row_total_incl_tax'] = ($item['dataItem']['item:price_incl_tax'] * $shipmentItem->getQty());
                                        $subTotalItems += $item['amount'];
                                        $taxAmountItems += $item['dataItem']['item:tax_amount'];
                                        $discountAmountItem += $item['discount_amount'];
                                        $data[] = $item;
                                    }
                                }
                            }
                            $attrExtensionShipment = $shipment->getExtensionAttributes();
                            if($attrExtensionShipment->getStatus()){
                                $orderShipment['status'] = $attrExtensionShipment->getStatus();
                            }
                            $percentageShipmentInOrder = $this->percentageShipmentInOrder($subTotalItems, $order->getSubtotalInclTax());
                            $orderShipment['shipping_amount'] = $orderShipment['shipping_amount']*$percentageShipmentInOrder;
                            $orderShipment['discount_amount'] = $discountAmountItem;
                            $orderShipment['shipment'] = $shipment;
                            $orderShipment['subtotal'] = $subTotalItems;
                            $orderShipment['grand_total'] = $subTotalItems + $orderShipment['shipping_amount'] - abs($orderShipment['discount_amount']);
                            $orderShipment['tax_amount'] = $taxAmountItems;
                            $orderShipment['number_shipment'] = count($shipments);
                            $orderShipment['product_total'] = count($shipmentItems);
                            $orderShipment['items'] = $data;
                            $orderShipment['source_code'] = $sourceCode;
                            $exportShipData[] = $orderShipment;
                        }
                    }
                    foreach ($exportShipData as $orderToShip) {
                        $this->gateway->exportSource($orderToShip);
                    }
                }else {
                    $this->gateway->exportSource($this->orderData[$incrementId]);
                }
            }
        }

        if (null === $this->_headerCols) {
            $this->setHeaderCols(array_keys($rowData));
        }
        if (null === $this->_headerCols) {
            $this->_headerCols = [];
        }
        $this->_fileHandler->writeCsv(
            array_merge(
                $this->_headerCols,
                array_intersect_key($rowData, $this->_headerCols)
            ),
            $this->_delimiter,
            $this->_enclosure
        );
        return $this;
    }

    /**
     * @param array $rowData
     */
    protected function prepareOrderData(array $rowData)
    {
        if (isset($rowData['increment_id'])
            && ((!isset($this->orderData[$rowData['increment_id']])
                    && !empty($this->orderData))
                || (empty($this->orderData)))
        ) {
            $this->orderData = null;
            $addressFirstname = !empty($rowData['address:firstname'])
                ? $rowData['address:firstname'] : 'Test';
            $addressLastname = !empty($rowData['address:lastname'])
                ? $rowData['address:lastname'] : 'Test';

            if (isset($rowData['customer_id'])) {
                $customer = $this->customerRepository
                    ->getById($rowData['customer_id']);
                $customerNetsuiteInternalId = $customer
                    ->getCustomAttribute('netsuite_internal_id');
            }

            $items = $this->prepareOrderItemData($rowData, $rowData['increment_id']);
            $this->orderData[$rowData['increment_id']] = [
                'entity_id' => $rowData['entity_id'],
                'increment_id' => $rowData['increment_id'],
                'items' => !empty($items)?
                    [$items] : [],
                'product_total' => $rowData['total_item_count'],
                'email' => $rowData['customer_email'],
                'firstname' => !empty($rowData['customer_firstname'])?
                    $rowData['customer_firstname'] : $addressFirstname,
                'lastname' => !empty($rowData['customer_lastname'])?
                    $rowData['customer_lastname'] : $addressLastname,
                'phone' => !empty($rowData['address:telephone'])?
                    $rowData['address:telephone'] : '',
                'discount_amount' => !empty($rowData['base_discount_amount'])?
                    $rowData['base_discount_amount'] : '',
                'shipping_amount' => !empty($rowData['shipping_amount'])?
                    $rowData['shipping_amount'] : '',
                'payment:po_number' => !empty($rowData['payment:po_number'])?
                    $rowData['payment:po_number'] : '',
                'netsuite_internal_id' =>
                    isset($rowData['netsuite_internal_id'])?
                        $rowData['netsuite_internal_id']
                        : '',
                'customer_id' =>
                    (isset($rowData['customer_id']))?
                        $rowData['customer_id'] : null,
                'customer_netsuite_internal_id' =>
                    (!empty($customerNetsuiteInternalId)) ?
                        $customerNetsuiteInternalId->getValue() : null,
            ];

            if(isset($rowData['address:address_type']) && ($rowData['address:address_type'] =='shipping')) {
                $this->orderData[$rowData['increment_id']]['shipping_address'] = $this->prepareAddressData($rowData);
            }

            if(isset($rowData[FilterStockLocation::STOCK_LOCATION])) {
                $this->orderData[$rowData['increment_id']][FilterStockLocation::STOCK_LOCATION] = !empty($rowData[FilterStockLocation::STOCK_LOCATION])?
                    $rowData[FilterStockLocation::STOCK_LOCATION] : '';
            }

        } elseif (!isset($rowData['increment_id']) && !empty($this->orderData)) {
            reset($this->orderData);
            $incrementId = key($this->orderData);
            $orderItemData = $this->prepareOrderItemData($rowData, $incrementId);
            if (!empty($orderItemData)) {
                $this->orderData[$incrementId]['items'][] = $orderItemData;
            }
            if(isset($rowData['address:address_type']) && ($rowData['address:address_type'] =='billing')) {
                $this->orderData[$incrementId]['billing_address'] = $this->prepareAddressData($rowData);
            }

        }
    }

    /**
     * @param array $rowData
     * @param $incrementId
     * @return array
     */
    protected function prepareOrderItemData(array $rowData, $incrementId)
    {
        $data = [];
        $parent_id = isset($rowData['item:parent_item_id']) ? $rowData['item:parent_item_id'] : null;
        if (is_null($parent_id) && !empty($rowData['item:sku'])) {
            try {
                $product = $this->productRepository->get($rowData['item:sku'], true);
                $netsuiteInternalId = $product->getData('netsuite_internal_id');
            } catch (NoSuchEntityException $e) {
                $netsuiteInternalId = null;
            }

            $data = [
                'sku' => $rowData['item:sku'],
                'internalId' => $netsuiteInternalId,
                'quantity' => $rowData['item:qty_ordered'],
                'amount' => $rowData['item:row_total_incl_tax'],
                'price' => $rowData['item:price'],
                'taxCode' => $rowData['item:tax_amount'],
                'discount_amount' => $rowData['item:discount_amount']
            ];

            // Customize add dataItem to array
            $dataItem = [];
            foreach ($rowData as $attr => $value){
                if(strpos($attr,'item:') !== false){
                    $dataItem[$attr] = $value;
                    continue;
                }
            }
            $data['dataItem'] = $dataItem;
            //endl
        }
        return $data;
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function prepareAddressData(array $rowData)
    {
        $data = [
            'street' => !empty($rowData['address:street']) ?
                $rowData['address:street'] : '',
            'phone' =>  !empty($rowData['address:telephone']) ?
                $rowData['address:telephone'] : '',
            'country' => !empty($rowData['address:country_id']) ?
                $rowData['address:country_id'] : '',
            'city' => !empty($rowData['address:city']) ?
                $rowData['address:city'] : '',
            'state' => !empty($rowData['address:region']) ?
                $rowData['address:region'] : '',
            'zip' => !empty($rowData['address:postcode']) ?
                $rowData['address:postcode'] : '',
            'firstname' => !empty($rowData['address:firstname']) ?
                $rowData['address:firstname'] : '',
            'lastname' => !empty($rowData['address:lastname']) ?
                $rowData['address:lastname'] : '',
            'addressee' => !empty($rowData['address:firstname'])
            && !empty($rowData['address:lastname']) ?
                $rowData['address:firstname'] . ' ' . $rowData['address:lastname'] : '',
        ];

        return $data;
    }

    /**
     * @param $subtotalShipment
     * @param $subtotalOrder
     * @return float|int
     */
    private function percentageShipmentInOrder($subtotalShipment, $subtotalOrder){
        return $subtotalShipment/$subtotalOrder;
    }

    /**
     * @param ShipmentInterface[] $shipments
     * @param $filterValue
     * @return array
     */
    private function filterShipmentsByStockLocation($shipments, $filterValue): array
    {
        if($filterValue == '') {
            return $shipments;
        }
        $result = [];
        foreach ($shipments as $shipment) {
            if ($shipment->getExtensionAttributes()->getSourceCode() == $filterValue) {
                $result[] = $shipment;
            }
        }

        return $result;
    }

    /**
     * Remove entity shipment
     * @param array $rowData
     * @return array
     */
    private function ignoreShipmentDataTable(array $rowData): array
    {
        foreach($rowData as $attr => $value) {
            if(strpos($attr,'shipment:') !== false){
                unset($rowData[$attr]);
            }else if (strpos($attr,'shipment_item:') !== false){
                unset($rowData[$attr]);
            }
        }
        return $rowData;
    }

    /**
     * @param array $shipments
     * @return ShipmentInterface[]|null
     */
    private function ignoreShipmentFromStore(array $shipments): array
    {
        foreach($shipments as $shipmentId => $shipment) {
            if(!$this->storeShipping->isShippingFromWareHouse($shipment->getExtensionAttributes()->getSourceCode())) {
                unset($shipments[$shipmentId]);
            }
        }
        return $shipments;
    }
}
