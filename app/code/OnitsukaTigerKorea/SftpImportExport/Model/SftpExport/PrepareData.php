<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\SftpImportExport\Model\SftpExport;

use Amasty\Rma\Model\Request\Request;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditMemoCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\CollectionFactory as CmItemCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;

class PrepareData
{
    /**
     * @var CollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * @var CmItemCollectionFactory
     */
    protected $cmItemCollectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Json
     */
    protected $serializer;

    /**
     * @var CreditMemoCollectionFactory
     */
    protected $creditMemoCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ExportXml
     */
    protected $exportXml;

    /**
     * PrepareData constructor.
     * @param CollectionFactory $orderItemCollectionFactory
     * @param CmItemCollectionFactory $cmItemCollectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param Json $serializer
     * @param CreditMemoCollectionFactory $creditMemoCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ExportXml $exportXml
     */
    public function __construct(
        CollectionFactory $orderItemCollectionFactory,
        CmItemCollectionFactory $cmItemCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        Json $serializer,
        CreditMemoCollectionFactory $creditMemoCollectionFactory,
        ProductRepositoryInterface $productRepository,
        ExportXml $exportXml
    ) {
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->cmItemCollectionFactory = $cmItemCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->serializer = $serializer;
        $this->creditMemoCollectionFactory = $creditMemoCollectionFactory;
        $this->productRepository = $productRepository;
        $this->exportXml = $exportXml;
    }

    /**
     * @param ShipmentInterface $shipment
     * @return array
     */
    public function getOrderOrdSalesData(ShipmentInterface $shipment)
    {
        /** @var Order $order */
        $order = $shipment->getOrder();
        $couponLimit = $this->getCouponLimit($order);

        $orderEmoneyAmt = 0; //0 for phase 1
        $orderReviseAmt = 0; //0 for phase 1
        $deliveryCharge = $this->formatNumber($order->getShippingAmount());
        $deliveryAddCharge = 0; //0 for phase 1
        $deliveryChargeAmt = $deliveryAddCharge + $deliveryCharge;
        $orderSettleAmt = $this->formatNumber($order->getSubtotalInclTax()) - $this->formatNumber(abs((float)$order->getDiscountAmount()));
        $giftWrapPrice = $order->getData('mp_gift_wrap_amount') ?? 0;
        $settleAmt = $orderSettleAmt + $deliveryChargeAmt + $giftWrapPrice;
        $discount = $order->getDiscountAmount();
        $usedPoint = $order->getUsedPoint();
        if ($usedPoint > 0) {
            $discount = $order->getDiscountAmount() - $usedPoint;
            $orderEmoneyAmt = $usedPoint;
        }
        return [
            'brand_id' => 1,
            'cstmr_id' => 'BB990',
            'cstmr_sname' => 'OKR 자사몰',
            'act_date' => $this->getActDate($shipment, $shipment->getStoreId()),
            'order_no' => $this->exportXml->addPrefix($shipment->getId(), ExportXml::PREFIX_SHIPMENT),
            'origin_order_no' => $this->exportXml->addPrefix($order->getId(), ExportXml::PREFIX_ORDER),
            'origin_order_date' => date('Ymd', strtotime($order->getCreatedAt())),
            'order_type' => $order->getOriginalOrderId() ? 'rtn_exchange' : 'ship',
            'order_qty' => (int)$order->getTotalQtyOrdered(),
            'order_amt' => $this->formatNumber($order->getSubtotalInclTax()),
            'order_coupon_sale_amt' => $this->formatNumber(abs((float)$discount)),
            'order_emoney_amt' => $this->formatNumber($orderEmoneyAmt),
            'order_revise_amt' => $this->formatNumber($orderReviseAmt),
            'order_settle_amt' => $orderSettleAmt,
            'delivery_charge' => $deliveryCharge,
            'delivery_add_charge' => $this->formatNumber($deliveryAddCharge),
            'delivery_charge_amt' => $this->formatNumber($deliveryChargeAmt),
            'gift_wrap_price' => $this->formatNumber($giftWrapPrice),
            'settle_amt' => $this->formatNumber($settleAmt),
            'coupon_limit' => $couponLimit === '' ? $couponLimit : $this->formatNumber($couponLimit),
            'remark1' => $order->getIncrementId(),
            'remark2' => '',
            'remark3' => ''
        ];
    }

    /**
     * @param ShipmentInterface $shipment
     * @return array
     * @throws NoSuchEntityException
     */
    public function getOrderSalesData(ShipmentInterface $shipment)
    {
        $index = 1;
        /** @var Order $order */
        $order = $shipment->getOrder();
        $data = [];

        $totalItemsDiscountAmount = 0;
        $couponLimit = $this->getCouponLimit($order);
        $totalOrderDiscount = abs((float)$order->getDiscountAmount());
        $totalProductAmt = $order->getSubtotalInclTax();

        foreach ($order->getItems() as $orderItem) {
            $totalItems = count($order->getItems());
            if ($orderItem->getProductType() === 'configurable' || ($order->getOriginalOrderId() && $orderItem->getProductType() === 'simple')) {
                $product = $this->productRepository->get($orderItem->getSku());
                $giftWrapPrice = $order->getData('mp_gift_wrap_amount') ?? 0;
                $deliveryCharge = $this->formatNumber($order->getShippingAmount());
                $deliveryAddCharge = 0; //0 for phase 1
                $unitCouponSale = 0; //0 for phase 1
                $unitEmoney = 0; //0 for phase 1
                $unitReviseAmt = 0; //0 for phase 1
                $unitPrice = 0; //0 for phase 1
                $emoneyAmt = 0; //0 for phase 1
                $reviseAmt = 0; //0 for phase 1
                $deliveryChargeAmt = $deliveryAddCharge + $deliveryCharge;
                // calculation discount amount for last item
                $discountAmount  = $orderItem->getDiscountAmount();
                if ($totalItems / 2 != $index) {
                    $totalItemsDiscountAmount += $orderItem->getDiscountAmount();
                }

                if ($totalItems != 2 && $totalItems / 2 == $index) {
                    $discountAmount = abs((float)$order->getDiscountAmount()) - $totalItemsDiscountAmount;
                }

                $productSettleAmt = $this->formatNumber($totalProductAmt) - $this->formatNumber($totalOrderDiscount) - $emoneyAmt - $reviseAmt;
                $settleAmt = $this->formatNumber($productSettleAmt) + $deliveryChargeAmt + $this->formatNumber($giftWrapPrice);

                $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/salesD.log');
                $logger = new \Zend_Log();
                $logger->addWriter($writer);
                $logger->info('-----Logger Start-----');
                $logger->info('Item ID: ' . $orderItem->getId());
                $logger->info('Discount Before Used Points: ' . $discountAmount);

                $usedPoint = $orderItem->getUsedPoint();
                $logger->info('Used Points: ' . $usedPoint);
                if ($usedPoint > 0) {
                    $discountAmount = $discountAmount - $usedPoint;
                    $logger->info('Discount After Used Points: ' . $discountAmount);
                    $emoneyAmt = $this->formatNumber($usedPoint);
                }
                $logger->info('-----Logger End-----');
                $discountAmount = $this->formatNumber(abs((float)$discountAmount));
                $dataItem = [
                    'brand_id' => 2,
                    'cstmr_id' => 'BB990',
                    'cstmr_sname' => 'OKR 자사몰',
                    'act_date' => $this->getActDate($shipment, $shipment->getStoreId()),
                    'order_no' => $this->exportXml->addPrefix($shipment->getId(), ExportXml::PREFIX_SHIPMENT),
                    'origin_order_no' => $this->exportXml->addPrefix($order->getId(), ExportXml::PREFIX_ORDER),
                    'order_line_no' => $index,
                    'order_type' => $order->getOriginalOrderId() ? 'rtn_exchange' : 'ship',
                    'product_sku' => $product->getSkuWms(),
                    'product_qty' => (int)$orderItem->getQtyOrdered(),
                    'product_unit_price' => $this->formatNumber($orderItem->getPriceInclTax()),
                    'unit_coupon_sale' => $this->formatNumber($unitCouponSale),
                    'unit_emoney' => $unitEmoney,
                    'unit_revise_amt' => $unitReviseAmt,
                    'unit_price' => $unitPrice,
                    'product_amt' => $this->formatNumber($orderItem->getRowTotalInclTax()),
                    'coupon_sale_amt' => $discountAmount,
                    'emoney_amt' => $emoneyAmt,
                    'revise_amt' => $reviseAmt,
                    'product_settle_amt' => $productSettleAmt,
                    'delivery_charge' => $deliveryCharge,
                    'delivery_add_charge' => $this->formatNumber($deliveryAddCharge),
                    'delivery_charge_amt' => $this->formatNumber($deliveryChargeAmt),
                    'gift_wrap_price' => $this->formatNumber($giftWrapPrice),
                    'settle_amt' => $settleAmt,
                    'coupon_limit' => $couponLimit === '' ? $couponLimit : $this->formatNumber($couponLimit),
                    'remark1' => $order->getIncrementId(),
                    'remark2' => '',
                    'remark3' => ''
                ];
                $data[] = $dataItem;
                $index++;
            }
        }
        return $data;
    }

    /**
     * @param Request $rmaRequests
     * @return array
     * @throws NoSuchEntityException
     */
    public function getRmaSalesData(Request $rmaRequests)
    {
        $index = 1;
        /** @var Order $order */
        $order = $this->orderRepository->get($rmaRequests->getOrderId());
        $data = [];
        $deliveryAddCharge = 0; //0 for phase 1
        $unitCouponSale = 0; //0 for phase 1
        $unitEmoney = 0; //0 for phase 1
        $unitReviseAmt = 0; //0 for phase 1
        $unitPrice = 0; //0 for phase 1
        $emoneyAmt = 0; //0 for phase 1
        $reviseAmt = 0; //0 for phase 1
        $totalCreditMemoDiscount = 0;
        $totalProductAmt = 0;

        $couponLimit = $this->getCouponLimit($order);
        $creditMemos = $this->creditMemoCollectionFactory->create()
            ->addFieldToFilter('rma_request_id', $rmaRequests->getId());

        foreach ($creditMemos as $creditMemo) {
            $totalCreditMemoDiscount += $this->formatNumber(abs((float)$creditMemo->getDiscountAmount()));
            $totalProductAmt += $this->formatNumber($creditMemo->getSubtotalInclTax());
        }

        foreach ($creditMemos as $creditMemo) {
            $totalItemsDiscountAmount = 0;
            $itemIndex = 1;
            $creditMemoItems = $this->cmItemCollectionFactory->create()
                ->addFieldToFilter('parent_id', $creditMemo->getId());
            $creditMemoItems->getSelect()
                ->join(
                    ['cpe' => 'catalog_product_entity'],
                    'main_table.product_id = cpe.entity_id',
                    []
                )
                ->where('cpe.type_id = ?', 'configurable');
            $totalItem = $creditMemoItems->count();

            foreach ($creditMemoItems as $creditMemoItem) {
                $orderItemCreditmemo = $this->getOrderItemById($creditMemoItem->getOrderItemId());
                if ($orderItemCreditmemo->getProductType() === 'configurable') {
                    $product = $this->productRepository->get($creditMemoItem->getSku());
                    $deliveryCharge = $this->formatNumber($order->getShippingAmount());

                    $deliveryChargeAmt = $deliveryAddCharge + $deliveryCharge;
                    $giftWrapPrice = 0; // not return price in case return
                    $discountAmount = $creditMemoItem->getDiscountAmount();
                    // calculation discount amount for last item
                    if ($totalItem / 2 != $itemIndex) {
                        $totalItemsDiscountAmount += $creditMemoItem->getDiscountAmount();
                    }

                    if ($totalItem != 2 && $totalItem / 2 == $itemIndex) {
                        $discountAmount = abs((float)$creditMemo->getDiscountAmount()) - $totalItemsDiscountAmount;
                    }

                    $productSettleAmt = $totalProductAmt - $totalCreditMemoDiscount - $reviseAmt;
                    $settleAmt = $productSettleAmt - $deliveryChargeAmt - $this->formatNumber($giftWrapPrice);
                    $totalUsedPoint = $orderItemCreditmemo->getUsedPoint();

                    if ($totalUsedPoint > 0) {
                        $orderItemQty = (int)$orderItemCreditmemo->getQtyOrdered();
                        if ($totalUsedPoint > 0) {
                            $usedPoint = ($totalUsedPoint / $orderItemQty) * $creditMemoItem->getQty();
                            $discountAmount = $discountAmount - $usedPoint;
                            //$discountAmount = $this->formatNumber(abs((float)$discountAmount));
                            $emoneyAmt = $this->formatNumber($usedPoint);
                        }
                    }
                    $discountAmount = $this->formatNumber(abs((float)$discountAmount));
                    $dataItem = [
                        'brand_id' => 2,
                        'cstmr_id' => 'BB990',
                        'cstmr_sname' => 'OKR 자사몰',
                        'act_date' => $this->exportXml->getTimeZoneDatetimeString('Ymd', $rmaRequests->getStoreId()),
                        'order_no' => $this->exportXml->addPrefix($rmaRequests->getId(), ExportXml::PREFIX_RETURN),
                        'origin_order_no' => $this->exportXml->addPrefix($order->getId(), ExportXml::PREFIX_ORDER),
                        'order_line_no' => $index,
                        'order_type' => 'return',
                        'product_sku' => $orderItemCreditmemo->getSkuWms() ? $orderItemCreditmemo->getSkuWms() : $product->getSkuWms(),
                        'product_qty' => (int)$creditMemoItem->getQty(),
                        'product_unit_price' => $this->formatNumber($creditMemoItem->getPriceInclTax()),
                        'unit_coupon_sale' => $this->formatNumber($unitCouponSale),
                        'unit_emoney' => $unitEmoney,
                        'unit_revise_amt' => $unitReviseAmt,
                        'unit_price' => $unitPrice,
                        'product_amt' => $this->formatNumber($creditMemoItem->getRowTotalInclTax()),
                        'coupon_sale_amt' => $discountAmount,
                        'emoney_amt' => $emoneyAmt,
                        'revise_amt' => $reviseAmt,
                        'product_settle_amt' => $this->formatNumber($productSettleAmt),
                        'delivery_charge' => $deliveryCharge,
                        'delivery_add_charge' => $deliveryAddCharge,
                        'delivery_charge_amt' => $this->formatNumber($deliveryChargeAmt),
                        'gift_wrap_price' => $this->formatNumber($giftWrapPrice),
                        'settle_amt' => $this->formatNumber($settleAmt),
                        'coupon_limit' => $couponLimit === '' ? $couponLimit : $this->formatNumber($couponLimit),
                        'remark1' => $order->getIncrementId(),
                        'remark2' => '',
                        'remark3' => ''
                    ];
                    $data[] = $dataItem;
                    $index++;
                    $itemIndex++;
                }
            }
        }
        return $data;
    }

    /**
     * @param Request $rmaRequests
     * @return array
     */
    public function getRmaOrdSalesData(Request $rmaRequests)
    {
        /** @var Order $order */
        $order = $this->orderRepository->get($rmaRequests->getOrderId());
        $orderQty = 0;
        $deliveryCharge = $this->formatNumber($order->getShippingAmount());
        $giftWrapPrice = 0; // not return price in case return
        $deliveryAddCharge = 0; // for phase 1
        $deliveryChargeAmt = $deliveryAddCharge + $deliveryCharge;
        $orderSettleAmt = 0;
        $orderAmt = 0;
        $orderCouponSaleAtm = 0;
        $orderReviseAmt = 0;
        $couponLimit = $this->getCouponLimit($order);
        $orderEmoneyAmt = 0; // for phase 1
        $usedPoint = 0;

        $creditMemos = $this->creditMemoCollectionFactory->create()
            ->addFieldToFilter('rma_request_id', $rmaRequests->getId());
        if ($creditMemos->count()) {
            foreach ($creditMemos as $creditMemo) {
                $creditMemoItems = $this->cmItemCollectionFactory->create()
                    ->addFieldToFilter('parent_id', $creditMemo->getId());
                foreach ($creditMemoItems as $creditMemoItem) {
                    $orderItemCreditmemo = $this->getOrderItemById($creditMemoItem->getOrderItemId());
                    if ($orderItemCreditmemo->getProductType() === 'configurable') {
                        $orderQty += $creditMemoItem->getQty();
                        $orderItem =  $creditMemoItem->getOrderItem();
                        $orderItemQty = (int)$orderItem->getQtyOrdered();
                        $totalUsedPoint = $orderItem->getUsedPoint();
                        if ($totalUsedPoint > 0) {
                            $usedPoint += ($totalUsedPoint / $orderItemQty) * $creditMemoItem->getQty();
                        }
                    }
                }
                $orderAmt += $this->formatNumber($creditMemo->getSubtotalInclTax());
                $orderCouponSaleAtm += $this->formatNumber(abs((float)$creditMemo->getDiscountAmount()));
                $orderReviseAmt += $this->formatNumber($creditMemo->getAdjustmentPositive()) + $this->formatNumber($creditMemo->getAdjustmentNegative());
                $orderSettleAmt = $orderAmt - ($orderCouponSaleAtm + 0 + $orderReviseAmt);
            }
            $settleAmt = $orderSettleAmt + $giftWrapPrice + $deliveryChargeAmt;
            if ($usedPoint > 0) {
                $orderCouponSaleAtm = $orderCouponSaleAtm - $usedPoint;
                $orderEmoneyAmt = $this->formatNumber($usedPoint);
            }

            return [
                'brand_id' => 1,
                'cstmr_id' => 'BB990',
                'cstmr_sname' => 'OKR 자사몰',
                'act_date' => $this->exportXml->getTimeZoneDatetimeString('Ymd', $rmaRequests->getStoreId()),
                'order_no' => $this->exportXml->addPrefix($rmaRequests->getId(), ExportXml::PREFIX_RETURN),
                'origin_order_no' => $this->exportXml->addPrefix($order->getId(), ExportXml::PREFIX_ORDER),
                'origin_order_date' => date('Ymd', strtotime($order->getCreatedAt())),
                'order_type' => 'return',
                'order_qty' => (int)$orderQty,
                'order_amt' => $this->formatNumber($orderAmt),
                'order_coupon_sale_amt' => $this->formatNumber($orderCouponSaleAtm),
                'order_emoney_amt' => $orderEmoneyAmt,
                'order_revise_amt' => $orderReviseAmt,
                'order_settle_amt' => $orderSettleAmt,
                'delivery_charge' => $deliveryCharge,
                'delivery_add_charge' => $this->formatNumber($deliveryAddCharge),
                'delivery_charge_amt' => $this->formatNumber($deliveryChargeAmt),
                'gift_wrap_price' => $this->formatNumber($giftWrapPrice),
                'settle_amt' => $settleAmt,
                'coupon_limit' => $couponLimit === '' ? $couponLimit : $this->formatNumber($couponLimit),
                'remark1' => $order->getIncrementId(),
                'remark2' => '',
                'remark3' => ''
            ];
        }
        return [];
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getOrderItemById($id)
    {
        return $this->orderItemCollectionFactory->create()
            ->addFieldToFilter(OrderItemInterface::ITEM_ID, (int)$id)
            ->fetchItem();
    }

    /**
     * @param $order
     * @return mixed
     */
    public function getCouponLimit($order)
    {
        $condition = null;
        $values = [];
        $couponConditionSerializedRule = $order->getData('coupon_condition_serialized_rule');
        $order->getAppliedRuleIds();
        if ($order->getData('coupon_condition_serialized_rule')) {
            $ruleIds = array_unique(explode(',', $order->getAppliedRuleIds()));
            foreach ($ruleIds as $ruleId) {
                $serializedRule = json_decode($couponConditionSerializedRule);
                $conditions = $this->serializer->unserialize($serializedRule->{$ruleId}->condition);

                if (is_array($conditions) && !empty($conditions)) {
                    foreach ($conditions as $key => $condition) {
                        if ($key === 'conditions') {
                            foreach ($condition as $value) {
                                if ($value['attribute'] === 'base_subtotal_with_discount' || $value['attribute'] === 'base_subtotal') {
                                    $values[] = $value['value'];
                                }
                            }
                        }
                    }
                }
            }
            if (empty($values)) {
                return 0;
            }
            return max($values);
        } else {
            return '';
        }
    }

    protected function formatNumber($number)
    {
        return round((float)$number, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * @param $shipment
     * @param $storeId
     * @return mixed|string
     */
    private function getActDate($shipment, $storeId): mixed
    {
        return $this->exportXml->getTimeZoneDatetimeString('Ymd', $storeId);
        /*return $shipment->getData('act_release_date') ? date('Ymd', strtotime($shipment->getData('act_release_date')))
            : $this->exportXml->getTimeZoneDatetimeString('Ymd', $storeId);*/
    }
}
