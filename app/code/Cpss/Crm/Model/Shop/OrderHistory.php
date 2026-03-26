<?php

namespace Cpss\Crm\Model\Shop;

use Cpss\Crm\Api\Shop\OrderHistoryInterface;
use Magento\Framework\App\RequestInterface;
use Cpss\Crm\Model\Shop\Config\Result;
use Cpss\Crm\Helper\Validation;
use Cpss\Crm\Model\ResourceModel\ShopReceipt\CollectionFactory as ShopReceipt;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Cpss\Crm\Model\Shop\Config\Param;
use Cpss\Crm\Model\Shop\Config\DB;

class OrderHistory implements OrderHistoryInterface
{
    const DATE_FORMAT = 'Ymd';

    protected $requestInterface;
    protected $validationHelper;
    protected $shopReceipt;
    protected $searchCriteria;
    protected $posHelper;

    public function __construct(
        RequestInterface $requestInterface,
        Validation $validationHelper,
        ShopReceipt $shopReceipt,
        SearchCriteriaBuilder $searchCriteria,
        \Cpss\Pos\Helper\Data $posHelper
    ) {
        $this->requestInterface = $requestInterface;
        $this->validationHelper = $validationHelper;
        $this->shopReceipt = $shopReceipt;
        $this->searchCriteria = $searchCriteria;
        $this->posHelper = $posHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderHistory()
    {
        try {
            $params = $this->requestInterface->getParams();
            $response = [];
            $resultCode = Result::SUCCESS;
            $msg = "";
            $data = [];
            // Validate Params
            $resultCode = $this->validationHelper->validateParams(Param::SHOP_ORDER_HISTORY_PARAMS, $params);
            if ($resultCode === Result::SUCCESS) {
                $resultCode = ($this->validationHelper->validateAccessToken($params[Param::SHOP_ID], $params[Param::ACCESS_TOKEN])) ?  Result::SUCCESS : Result::AUTH_FAILED;
            }

            $startDate = isset($params[Param::START_DATE]) ? $params[Param::START_DATE] : null;
            $endDate = isset($params[Param::END_DATE]) ? $params[Param::END_DATE] : null;

            if ($resultCode == Result::SUCCESS) {
                $resultCode = $this->validateDate($startDate, $endDate);
            }

            if ($resultCode == Result::SUCCESS) {
                $collection = $this->shopReceipt->create();

                $startDateTime = $this->generateDate($startDate);
                $endDatetime = $this->generateDate($endDate, false);
                $purchasedDataFilter = "(`purchase_date` >= '{$startDateTime}' AND `purchase_date` <= '{$endDatetime}' AND `transaction_type` = 1)";
                $returnDataFilter = "(`return_date` >= '{$startDateTime}' AND `return_date` <= '{$endDatetime}' AND `transaction_type` = 2)";

                $collection
                    ->getSelect()
                    ->where(new \Zend_Db_Expr($purchasedDataFilter . " OR " . $returnDataFilter));

                $collection->addFieldToFilter('shop_id', array('eq' => $params[Param::SHOP_ID]));

                if (isset($params[Param::TERMINAL_NO]) && !empty($params[Param::TERMINAL_NO])) {
                    $collection->addFieldToFilter('pos_terminal_no', array('eq' => $params[Param::TERMINAL_NO]));
                }

                if (isset($params[Param::TRANSACTION_TYPE]) && !empty($params[Param::TRANSACTION_TYPE])) {
                    $collection->addFieldToFilter('transaction_type', array('eq' => $params[Param::TRANSACTION_TYPE]));
                }

                if (isset($params[Param::MEMBER_ID]) && !empty($params[Param::MEMBER_ID])) {
                    $collection->addFieldToFilter('member_id', array('eq' => $params[Param::MEMBER_ID]));
                }

                if (isset($params[Param::PURCHASE_ID]) && !empty($params[Param::PURCHASE_ID])) {
                    $collection->addFieldToFilter('purchase_id', array('eq' => $params[Param::PURCHASE_ID]));
                }

                $data = [];

                foreach ($collection as $receipt) {
                    $transactionType = $receipt->getTransactionType() ?? '';
                    $purchaseId = '';
                    $returnPurchaseId = '';


                    if ($transactionType != '') {
                        $purchaseId = $receipt->getPurchaseId();
                        $purchaseTransactionDateTime = $receipt->getPurchaseDate() ? date('YmdHis', strtotime($receipt->getPurchaseDate())) : '';
                        $returnTransactionDateTime = $receipt->getReturnDate() ? date('YmdHis', strtotime($receipt->getReturnDate())) : '';
                        $addedPointTransactionDateTime = $receipt->getAddedPointDate() ? date('YmdHis', strtotime($receipt->getAddedPointDate())) : '';
                        $cancelPointTransactionDateTime = $receipt->getCancelPointDate() ? date('YmdHis', strtotime($receipt->getCancelPointDate())) : '';
                        $returnPurchaseId = $receipt->getReturnPurchaseId() ?? '';
                    }

                    $purchasedData = [
                        "purchaseId" => $purchaseId,
                        "terminalNo" => $receipt->getPosTerminalNo() ?? '',
                        "receiptNo" => $receipt->getReceiptNo(),
                        "transactionType" => 1,
                        "transactionDateTime" => $this->posHelper->convertTimezone($purchaseTransactionDateTime, "UTC", "YmdHis"),
                        "totalAmount" => (float) $receipt->getTotalAmount(),
                        "discountAmount" => (float) $receipt->getDiscountAmount(),
                        "totalTax" => (float) $receipt->getTotalTax(),
                        "memberId" => $receipt->getMemberId() ?? '',
                        "countryCode" => $receipt->getCountryCode() ?? '',
                        "usedPoint" => $receipt->getUsedPoint(),
                        "addedPoint" => $receipt->getAddedPoint(),
                        "pointTransactionDateTime" => $addedPointTransactionDateTime,
                        "pointHistoryId" => $receipt->getPointHistoryId() ?? '',
                        'returnId' => $returnPurchaseId
                    ];

                    $purchaseDateInt = strtotime($receipt->getPurchaseDate());

                    if ($purchaseDateInt >= strtotime($startDateTime) && $purchaseDateInt <= strtotime($endDatetime)) {
                        $data[] = $purchasedData;
                    }
                    
                    $returnDateInt = strtotime($receipt->getReturnDate());

                    if ($transactionType == 2 && $returnDateInt >= strtotime($startDateTime) && $returnDateInt <= strtotime($endDatetime)) {
                        $prevData = $purchasedData;
                        $data[] = [
                            "purchaseId" => $prevData["purchaseId"],
                            "terminalNo" => $prevData["terminalNo"],
                            "receiptNo" => $prevData["receiptNo"],
                            "transactionType" => 2,
                            "transactionDateTime" => $this->posHelper->convertTimezone($returnTransactionDateTime, "UTC", "YmdHis"),
                            "totalAmount" => ($prevData["totalAmount"] > 0) ? -$prevData["totalAmount"] : $prevData["totalAmount"],
                            "discountAmount" => ($prevData["discountAmount"] > 0) ? -$prevData["discountAmount"] : $prevData["discountAmount"],
                            "totalTax" => ($prevData["totalTax"] > 0) ? -$prevData["totalTax"] : $prevData["totalTax"],
                            "memberId" => $prevData["memberId"],
                            "countryCode" => $prevData["countryCode"],
                            "usedPoint" => -$prevData["usedPoint"],
                            "addedPoint" => -$prevData["addedPoint"],
                            "pointTransactionDateTime" => $cancelPointTransactionDateTime,
                            "pointHistoryId" => $prevData["pointHistoryId"],
                            "returnId" => $prevData["returnId"]
                        ];
                    }
                }
            }

            if ($resultCode == Result::SUCCESS) {
                $response = [
                    'resultCode' => $resultCode,
                    'resultExplanation' => Result::RESULT_CODES[$resultCode],
                    'shopId' => $params[Param::SHOP_ID],
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'shopPurchaseList' => $data
                ];
            } else {
                $response = [
                    'resultCode' => $resultCode,
                    'resultExplanation' => Result::RESULT_CODES[$resultCode]
                ];
            }
            $response = $this->validationHelper->convertArrayValuesToString($response);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            exit;
        } catch (\Exception $e) {
            $resultCode = Result::INTERNAL_ERROR;
            $response = [
                'resultCode' => $resultCode,
                'resultExplanation' => Result::RESULT_CODES[$resultCode],
            ];

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($e->getMessage());
            exit;
        }
    }

    /**
     * Validate Date
     * * YYYYMMDD format
     * * 90 Days Max Period
     *
     * @param  string $start
     * @param  string $end
     * @return bool
     */
    private function validateDate($start, $end)
    {
        try {
            if (!$this->validationHelper->dateFormat($start, self::DATE_FORMAT)
                && !$this->validationHelper->dateFormat($end, self::DATE_FORMAT)
            ) {
                return Result::INVALID_SPECIFIED_PERIOD;
            }

            $start = date_create($start);
            $end = date_create($end);

            $diff = (int) date_diff($start, $end)->format('%R%a');

            if ($start > $end || $diff > 90) {
                return Result::INVALID_SPECIFIED_PERIOD;
            }
            return Result::SUCCESS;
        } catch (\Exception $e) {
            return Result::INVALID_SPECIFIED_PERIOD;
        }
    }

    /**
     * Generate date with time
     * @param string $date
     * @param bool isStartTimeOption
     * @return string
     */
    public function generateDate($date, $isStartTimeOption = true)
    {
        $newDate = new \DateTime($date);

        if ($isStartTimeOption) {
            $newDate->setTime(0, 0, 0);
        } else {
            $newDate->setTime(23, 59, 59);
        }

        $formattedDate = $this->posHelper->convertTimezone($newDate->format('Y-m-d H:i:s'));
        return $formattedDate;
    }
}
