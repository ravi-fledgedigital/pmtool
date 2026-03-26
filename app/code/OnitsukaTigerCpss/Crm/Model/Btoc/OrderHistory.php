<?php

namespace OnitsukaTigerCpss\Crm\Model\Btoc;

use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\Btoc\Config\Result;
use Cpss\Crm\Model\ResourceModel\ShopReceipt\CollectionFactory as ShopReceiptCollection;
use Cpss\Pos\Helper\Data;
use Cpss\Pos\Model\ResourceModel\PosData\CollectionFactory as PosDataCollection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollection;
use OnitsukaTigerCpss\Crm\Helper\Data as CustomerHelper;
use OnitsukaTigerCpss\Crm\Helper\MemberValidation;
use OnitsukaTigerCpss\Crm\Model\Btoc\Config\Param;

class OrderHistory extends \Cpss\Crm\Model\Btoc\OrderHistory
{

    /**
     * @var TimezoneInterface
     */
    protected $timezone;
    protected $customerHelper;
    protected $validation;

    public function __construct(
        RequestInterface      $request,
        Logger                $logger,
        OrderCollection       $orderCollection,
        ShopReceiptCollection $shopReceiptCollection,
        PosDataCollection     $posDataCollection,
        MemberValidation      $validation,
        CustomerHelper        $customerHelper,
        Data                  $posHelper,
        TimezoneInterface     $timezone
    )
    {
        $this->customerHelper = $customerHelper;
        $this->timezone = $timezone;
        $this->validation = $validation;
        parent::__construct(
            $request,
            $logger,
            $orderCollection,
            $shopReceiptCollection,
            $posDataCollection,
            $validation,
            $customerHelper,
            $posHelper);
    }

    public function getMemberOrderHistory()
    {
        header('Content-Type: application/json');
        $result = [];

        try {
            //Get Request Parameter
            $requestData = $this->request->getParams();
            $this->customerHelper->logDebug("ORDER HISTORY", $requestData);

            //Validate Request
            $resultCode = $this->validation->validateData(Param::REQUEST_MEMBER_ORDER_HISTORY_PARAMS, $requestData);

            // Bad Request
            // Invalid Params
            if ($resultCode !== Result::SUCCESS) {
                echo json_encode($this->getResultByCode($resultCode));
                exit;
            }
            $websiteId = $requestData[Param::SITE_ID];
            // Validate Auth
            $resultCode = $this->customerHelper->authByWebsite(false, false, $websiteId, $requestData);
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
     * Get EC Order Purcahse List by customer
     * @param string $memberId
     * @return array
     */
    public function getPurchaseList($memberId)
    {
        $ecPurchaseList = [];

        $orderCollection = $this->orderCollection->create();
        $orderCollection->addAttributeToFilter('customer_id', $memberId);

        foreach ($orderCollection as $order) {
            $isOrderCancel = $order->getStatus() == Order::STATE_CANCELED ?? FALSE;
            $addedPoint = $order->getAddedPoint();
            $usedPoint = $order->getUsedPoint();
            if ($order->getAcquiredPoint()) {
                $addedPoint = $isOrderCancel ? '-' . $order->getAddedPoint() : $order->getAddedPoint();
            }
            if ($order->getUsedPoint()) {
                $usedPoint = $isOrderCancel ? '-' . $order->getUsedPoint() : $order->getUsedPoint();
            }

            $ecPurchaseList[] = [
                "purchaseId" => $order->getIncrementId(),
                "orderStatus" => $order->getStatus(),
                "orderDateTime" => $this->getCreatedAtFormatted($order),
                "paymentMethod" => $order->getPayment()->getMethod(),
                //"shippingFeeAndCommissionFee"    => ( (int) $order->getShippingAmount() ?? 0) + ((int) $order->getVwCodFee() ?? 0),
                "totalAmount" => $isOrderCancel ? '-' . $order->getGrandTotal() : $order->getGrandTotal(),
                "discountAmount" => $order->getDiscountAmount(),
                "totalTax" => $isOrderCancel ? '-' . $order->getTaxAmount() : $order->getTaxAmount(),
                "usedPoint" => $usedPoint,
                "addedPoint" => $addedPoint,
                "pointTransactionDateTime" => '', //Not Final
                "pointHistoryId" => '', //Not Final
                "productDetailsList" => $this->getProductDetailsList($order)
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
                $product = $item->getProduct();

                $productDetailsList[] = [
                    "productId" => $item->getSku(),
                    "productName" => $item->getName(),
                    "color" => $product->getResource()->getAttribute('color')->getFrontend()->getValue($product),
                    "size" => $product->getResource()->getAttribute('qa_size')->getFrontend()->getValue($product),
                    "quantity" => (int)$parentItem->getQtyOrdered(),
                    "totalPrice" => $parentItem->getRowTotal(),
                    "discount" => $parentItem->getDiscountAmount(),
                    "taxAmount" => $parentItem->getTaxAmount(),
                ];
            }
        }

        return $productDetailsList;
    }

    /**
     * @param $order
     * @param string $format
     * @return string
     */
    public function getCreatedAtFormatted($order, string $format = 'YmdHis')
    {
        $datetime =  $this->formatDate($order->getCreatedAt(), \IntlDateFormatter::MEDIUM, true, $this->getTimezoneForStore($order->getStore()));
        return date($format,strtotime($datetime));
    }

    /**
     * Retrieve formatting date
     *
     * @param null|string|\DateTimeInterface $date
     * @param int $format
     * @param bool $showTime
     * @param null|string $timezone
     * @return string
     */
    public function formatDate(
        $date = null,
        $format = \IntlDateFormatter::SHORT,
        $showTime = false,
        $timezone = null
    ) {
        $date = $date instanceof \DateTimeInterface ? $date : new \DateTime($date ?? 'now');
        return $this->timezone->formatDateTime(
            $date,
            $format,
            $showTime ? $format : \IntlDateFormatter::NONE,
            null,
            $timezone
        );
    }
    /**
     * Get timezone for store
     *
     * @param mixed $store
     * @return string
     */
    public function getTimezoneForStore($store)
    {
        return $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
    }
}
