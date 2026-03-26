<?php

namespace Vaimo\OTAdobeDataLayer\Observer;

use Magento\Framework\Event\ObserverInterface;

class PlaceOrderAfter implements ObserverInterface
{   
    /**
     * @var \Vaimo\OTAdobeDataLayer\Helper\Data
    */
    protected $dataLayerHelper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
    */
    protected $orderFactory;

    /**
     * @var \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory
    */
    protected $transactions;

    /**
     * order placed after constructor.
     * @param \Vaimo\OTAdobeDataLayer\Helper\Data $dataLayerHelper
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions
     */
    public function __construct(
        \Vaimo\OTAdobeDataLayer\Helper\Data $dataLayerHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions
    )
    {
        $this->dataLayerHelper = $dataLayerHelper;
        $this->orderFactory = $orderFactory;
        $this->transactions = $transactions;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->dataLayerHelper->isEnabledAdobeLaunch()){
            $order = $observer->getEvent()->getOrder();

            $orderObj = $this->orderFactory->create()->load($order->getId());
            $orderItems = $orderObj->getAllItems();

            $transaction = $this->transactions->create()->addOrderIdFilter($order->getId())->getFirstItem();

            $transactionId = '';
            if($transaction){
                $transactionId = $transaction->getData('txn_id');
            }

            $orderItemsData = [];
            foreach ($orderItems as $items) {
                if($items->getProductType() == 'simple'){
                    $catName = '';
                    $product = $this->dataLayerHelper->loadProductById($items->getProductId());
                    if($product->getCategoryIds() && isset($product->getCategoryIds()[0])){
                        $category = $this->dataLayerHelper->getCategoryLoadById($product->getCategoryIds()[0]);
                        $catName = $category->getName();
                    }
                    $orderItemsData[] = [
                        'sku'=> ($product->getSku() ? $product->getSku() : ''),
                        'name' => ($product->getName() ? $product->getName() : ''),
                        'productId' => (int)$product->getId(),
                        'category' => ($catName ? $catName : ''),
                        'brand' => ($items->getBrands()) ? $items->getAttributeText('brands'): '',
                        'color' => ($product->getColor()) ? $product->getAttributeText('color'): '',
                        'size' => ($product->getQaSize()) ? $product->getAttributeText('qa_size'): '',
                        'quantity'=> (int)($items->getQtyOrdered() ? $items->getQtyOrdered() : 0),
                        'currencyCode' => ($order->getStoreCurrencyCode() ? $order->getStoreCurrencyCode() : ''),
                        'priceTotal' => floatval($product->getPrice()),
                        'discountAmount' => floatval(($items->getSpecialPrice() ? $items->getPrice()-$items->getSpecialPrice(): 0)),
                        'unitOfMeasureCode' => 'ft'
                    ];

                }
            }

            $payment = $order->getPayment();
            $method = $payment->getMethodInstance();
            $methodCode= $method->getCode();

            $retunrData = [
                'checkout' => ['cartID' => $order->getQuoteId()],
                'order' =>  [
                    'purchaseId' => ($order->getId() ? $order->getId() : ''),
                    'currencyCode'=> ($order->getStoreCurrencyCode() ? $order->getStoreCurrencyCode() :''),
                    'couponCode' => ($order->getCouponCode() ? $order->getCouponCode() :''),
                    'shipping' => floatval($order->getShippingAmount()),
                    'discount' => floatval($order->getDiscountAmount()),
                    'subTotal' => floatval($order->getSubtotal()),
                    'priceTotal' => floatval($order->getGrandtotal()),
                    'payment' => [
                        'transactionId' => ($transactionId ? $transactionId : ''),
                        'paymentAmount' => floatval($order->getGrandtotal()),
                        'paymentType'   => ($methodCode ? $methodCode :''),
                        'currencyCode'  => ($order->getStoreCurrencyCode() ? $order->getStoreCurrencyCode() : '')
                    ]
                ]

            ];

            $this->dataLayerHelper->setOrderDataEvent(['data' => $retunrData, 'productListItems' => $orderItemsData]);
            $this->dataLayerHelper->setPaymentOrderDataEvent(
                [
                    'data' => $retunrData,
                    'productListItems' => $orderItemsData
                ]
            );
        }
    }
}