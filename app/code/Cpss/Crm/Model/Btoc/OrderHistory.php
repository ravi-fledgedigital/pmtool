<?php

namespace Cpss\Crm\Model\Btoc;

use Cpss\Crm\Api\Btoc\OrderHistoryInterface;
use Cpss\Crm\Model\Btoc\Config\Result;
use Cpss\Crm\Model\Btoc\Config\Param;
use Magento\Framework\App\RequestInterface;
use Cpss\Crm\Logger\Logger;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollection;
use Cpss\Crm\Model\ResourceModel\ShopReceipt\CollectionFactory as ShopReceiptCollection;
use Cpss\Pos\Model\ResourceModel\PosData\CollectionFactory as PosDataCollection;
use Cpss\Crm\Helper\MemberValidation;
use Cpss\Crm\Helper\Customer as CustomerHelper;
use Cpss\Pos\Helper\Data;

class OrderHistory implements OrderHistoryInterface
{
    protected $request;
    protected $logger;
    protected $orderCollection;
    protected $shopReceiptCollection;
    protected $posDataCollection;
    protected $validation;
    protected $customerHelper;
    protected $posHelper;

    public function __construct(
        RequestInterface $request,
        Logger $logger,
        OrderCollection $orderCollection,
        ShopReceiptCollection $shopReceiptCollection,
        PosDataCollection $posDataCollection,
        MemberValidation $validation,
        CustomerHelper $customerHelper,
        Data $posHelper
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->orderCollection = $orderCollection;
        $this->shopReceiptCollection = $shopReceiptCollection;
        $this->posDataCollection = $posDataCollection;
        $this->validation = $validation;
        $this->customerHelper = $customerHelper;
        $this->posHelper = $posHelper;
    }

    /**
     * Get Member Order History
     * @return json
     */
    public function getMemberOrderHistory()
    {
        header('Content-Type: application/json');
        $result = [];

        try {
            //Get Request Parameter
            $requestData = $this->request->getParams();
            $this->customerHelper->logDebug("ORDER HISTORY", $requestData);

            //Validate Request
            $resultCode = $this->validation->validateParams(Param::MEMBER_ORDER_HISTORY_PARAMS, $requestData);

            // Bad Request
            // Invalid Params
            if ($resultCode !== Result::SUCCESS) {
                echo json_encode($this->getResultByCode($resultCode));
                exit;
            }

            // Validate Auth
            $resultCode = $this->customerHelper->auth(false, false, $requestData);

            // Unauthorized
            // Auth Failed
            if ($resultCode !== Result::SUCCESS) {
                echo json_encode($this->getResultByCode($resultCode));
                exit;
            }

            // OK
            // Create Response
            $result = $this->getResultByCode($resultCode);
            $result['shopPurchaseList'] = $this->getShopPurchaseList($requestData[Param::MEMBER_ID]);
            $result['ecPurchaseList'] = $this->getPurchaseList($requestData[Param::MEMBER_ID]);
        } catch (\Exception $e) {
            $result = $this->getResultByCode(Result::INTERNAL_ERROR);
            $this->logger->critical('getMemberOrderHistory: ' . $e->getMessage());
        }
        $result = $this->customerHelper->convertArrayValuesToString($result);

        echo json_encode($result);
        exit;
    }

    /**
     * Get result by code
     * @param string $resultCode
     * @return array
     */
    public function getResultByCode($resultCode)
    {
        return [
            "resultCode"        => $resultCode,
            "resultExplanation" => Result::RESULT_CODES[$resultCode]
        ];
    }

    /**
     * Get Shop Purchase List
     * @param string $memberId
     * @return array
     */
    public function getShopPurchaseList($memberId)
    {
        $shopPurchaseList = [];

        $shopReceiptCollection = $this->shopReceiptCollection->create();
        $shopReceiptCollection->addFieldToFilter('member_id', $memberId);

        foreach ($shopReceiptCollection as $receipt) {
            $transactionDateTime = $receipt->getPurchaseDate() ? date('YmdHis', strtotime($receipt->getPurchaseDate())) : '';
            $returnTransactionDateTime = $receipt->getReturnDate() ? date('YmdHis', strtotime($receipt->getReturnDate())) : '';
            $pointTransactionDateTime = $receipt->getAddedPointDate() ? date('YmdHis', strtotime($receipt->getAddedPointDate())) : '';

            if ($transType = $receipt->getTransactionType()) {
                if ($transactionDateTime) {
                    if ($transType == 1) {
                        $transactionDateTime = $this->posHelper->convertTimezone($transactionDateTime, "UTC", "YmdHis");
                    } elseif ($transType == 2) {
                        $transactionDateTime = $this->posHelper->convertTimezone($returnTransactionDateTime, "UTC", "YmdHis");
                    }
                }
            }

            $shopPurchaseList[] = [
                'shopId'                    => $this->customerHelper->getShopId(),
                'shopName'                  => $this->customerHelper->getShopName(),
                'purchaseId'                => $receipt->getPurchaseId(),
                'originPurchaseId'          => $receipt->getOriginPurchaseId() ?? '',
                'terminalNo'                => $receipt->getPosTerminalNo() ?? '',
                'receiptNo'                 => $receipt->getReceiptNo(),
                'transactionType'           => $receipt->getTransactionType() ?? '',
                'transactionDateTime'       => $transactionDateTime,
                'paymentMethod'             => $receipt->getPaymentMethod(),
                'totalAmount'               => (float)$receipt->getTotalAmount(),
                'discountAmount'            => (float)$receipt->getDiscountAmount(),
                'totalTax'                  => (float)$receipt->getTaxAmount(),
                'usedPoint'                 => $receipt->getUsedPoint(),
                'addedPoint'                => $receipt->getAddedPoint(),
                'pointTransactionDateTime'  => $pointTransactionDateTime,
                'pointHistoryId'            => $receipt->getPointHistoryId() ?? '',
                'productDetailsList'        => $this->getShopPurchaseDetails($receipt->getId())
            ];
        }

        return $shopPurchaseList;
    }

    /**
     * Get shop purchase details
     * @param int $realStoreId
     * @return array
     */
    public function getShopPurchaseDetails($realStoreId)
    {
        $shopPurchaseDetails = [];

        $postDataCollection = $this->posDataCollection->create();
        $postDataCollection->addFieldToFilter('sales_real_store_order_id', $realStoreId);

        foreach ($postDataCollection as $item) {
            $shopPurchaseDetails[] = [
                "productId"   => $item->getSku(),
                "productName" => $item->getProductName(),
                "color"       => $item->getColor(),
                "size"        => $item->getSize(),
                "quantity"    => (int)$item->getQty(),
                "totalPrice"  => (float)$item->getSubtotalAmount(),
                "discount"    => (float)$item->getDiscountAmount(),
                "taxAmount"   => (float)$item->getTaxAmount(),
            ];
        }

        return $shopPurchaseDetails;
    }

    /**
     * Get EC Order Purcahse List by customer
     * @param string $memberId
     * @return array
     */
    public function getPurchaseList($memberId)
    {
        $ecPurchaseList = [];

        $orderCollection = $this->orderCollection->create();
        $orderCollection->addAttributeToFilter('member_id', $memberId);

        foreach ($orderCollection as $order) {
            $ecPurchaseList[] = [
                "purchaseId"                => $order->getIncrementId(),
                "orderStatus"               => $order->getStatus(),
                "orderDateTime"             => date('YmdHms', strtotime($order->getCreatedAt())),
                "paymentMethod"             => $this->customerHelper->getConfigValue('payment/' . $order->getPayment()->getMethod() . '/title'),
                "shippingFeeAndCommissionFee"    => ( (int) $order->getShippingAmount() ?? 0) + ((int) $order->getVwCodFee() ?? 0),
                "totalAmount"               => round($order->getGrandTotal()),
                "discountAmount"            => round($order->getDiscountAmount()),
                "totalTax"                  => round($order->getTaxAmount()),
                "usedPoint"                 => $order->getUsedPoint() ?? '',
                "addedPoint"                => $order->getAcquiredPoint() ?? '',
                "pointTransactionDateTime"  => '', //Not Final
                "pointHistoryId"            => '', //Not Final
                "productDetailsList"        => $this->getProductDetailsList($order)
            ];
        }

        return $ecPurchaseList;
    }

    /**
     * Get order product details
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getProductDetailsList($order)
    {
        $productDetailsList = [];

        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() == 'simple') {
                $parentItem = $item->getParentItem() ? $item->getParentItem() : $item;
                $product= $item->getProduct();

                $productDetailsList[] = [
                    "productId"   => $item->getSku(),
                    "productName" => $item->getName(),
                    "color"       => $product->getResource()->getAttribute('color')->getFrontend()->getValue($product),
                    "size"        =>  $product->getResource()->getAttribute('qa_size')->getFrontend()->getValue($product),
                    "quantity"    => (int)$parentItem->getQtyOrdered(),
                    "totalPrice"  => round($parentItem->getRowTotal()),
                    "discount"    => round($parentItem->getDiscountAmount()),
                    "taxAmount"   => round($parentItem->getTaxAmount()),
                ];
            }
        }

        return $productDetailsList;
    }
}
