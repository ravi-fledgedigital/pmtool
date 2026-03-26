<?php

namespace OnitsukaTigerCpss\Crm\Model\Shop;

use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\ResourceModel\ShopReceipt\CollectionFactory as ShopReceipt;
use Cpss\Crm\Model\Shop\Config\Result;
use Cpss\Crm\Model\Shop\OrderHistory as CrmOrderHistory;
use Cpss\Pos\Helper\Data;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\Website;
use OnitsukaTigerCpss\Crm\Helper\MemberValidation;
use OnitsukaTigerCpss\Crm\Helper\Validation;
use OnitsukaTigerCpss\Crm\Model\Shop\Config\Param;

class OrderHistory extends CrmOrderHistory
{
    const DATE_FORMAT = 'Ymd';
    const MAX_PERIOD_TIME = 90;

    protected $requestInterface;
    protected $validationHelper;
    protected $shopReceipt;
    protected $searchCriteria;
    protected $posHelper;
    protected Website $website;

    protected $logger;

    public function __construct(
        RequestInterface      $requestInterface,
        Validation            $validationHelper,
        ShopReceipt           $shopReceipt,
        SearchCriteriaBuilder $searchCriteria,
        Data                  $posHelper,
        Website               $website,
        Logger                $logger
    ) {
        $this->requestInterface = $requestInterface;
        $this->validationHelper = $validationHelper;
        $this->shopReceipt = $shopReceipt;
        $this->searchCriteria = $searchCriteria;
        $this->posHelper = $posHelper;
        $this->website = $website;
        $this->logger = $logger;
        parent::__construct($requestInterface, $validationHelper, $shopReceipt, $searchCriteria, $posHelper);
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
            $website = false;
            if (isset($params[Param::SITE_ID])) {
                $website = $this->website->load($params[Param::SITE_ID]);
            }

            // Validate Params
            if ($website && $website->getId() && $website->getCode() == 'web_kr') {
                $resultCode = $this->validationHelper->validateParams(Param::KR_REQUEST_SHOP_ORDER_HISTORY_PARAMS, $params);
            } else {
                $resultCode = $this->validationHelper->validateParams(Param::REQUEST_SHOP_ORDER_HISTORY_PARAMS, $params);
            }

            //validate for SG
            /*if (!$website->getId() ||
                (!empty($website->getId()) && MemberValidation::WEBSITE_COUNTRY_CODE[$website->getId()] !== "SG")) {*/
            if (!$website || !$website->getId()) {
                $resultCode = Result::AUTH_FAILED;
                $this->logger->critical(__('Website is not found or %1', Result::RESULT_CODES[$resultCode]));
                $response = [
                    'resultCode' => $resultCode,
                    'resultExplanation' => Result::RESULT_CODES[$resultCode]
                ];
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($response);
                exit;
            }

            if ($resultCode === Result::SUCCESS) {
                $resultCode = ($this->validationHelper->validateAccessToken($params[Param::SHOP_ID], $params[Param::ACCESS_TOKEN])) ?
                    Result::SUCCESS : Result::AUTH_FAILED;
            }

            $startDate = isset($params[Param::START_DATE]) ? $params[Param::START_DATE] : null;
            $endDate = isset($params[Param::END_DATE]) ? $params[Param::END_DATE] : null;

            if ($resultCode == Result::SUCCESS) {
                $resultCode = $this->validateDate($startDate, $endDate);
            }

            if ($resultCode == Result::SUCCESS) {
                $collection = $this->shopReceipt->create();

                /*$startDateTime = $this->generateDate($startDate);
                $endDatetime = $this->generateDate($endDate, false);*/
                if ($website->getId() == 4) {
                    $startDateTime = $startDate . " 15:00:00";
                    $endDateTime = $endDate . " 14:59:59";
                } else {
                    $startDateTime = $startDate . " 16:00:00";
                    $endDateTime = $endDate . " 15:59:59";
                }

                $startDateTime = date('Y-m-d H:i:s', strtotime("$startDateTime -1 day"));
                $endDatetime = date('Y-m-d H:i:s', strtotime("$endDateTime"));

                $purchasedDataFilter = "(`purchase_date` >= '{$startDateTime}' AND `purchase_date` <= '{$endDatetime}')";
                $returnDataFilter = "(`return_date` >= '{$startDateTime}' AND `return_date` <= '{$endDatetime}')";
                $collection->addFieldToFilter('shop_id', ['eq' => $params[Param::SHOP_ID]]);

                if (isset($params[Param::TERMINAL_NO]) && !empty($params[Param::TERMINAL_NO])) {
                    $terminalNo = $params[Param::TERMINAL_NO];
                    $terminalNo = strlen($terminalNo) < 4 ? str_pad($terminalNo, 4, '0', STR_PAD_LEFT) : $terminalNo;
                    $collection->addFieldToFilter('pos_terminal_no', ['eq' => $terminalNo]);
                }

                if (isset($params[Param::TRANSACTION_TYPE]) && !empty($params[Param::TRANSACTION_TYPE])) {
                    $collection->addFieldToFilter('transaction_type', ['eq' => $params[Param::TRANSACTION_TYPE]]);
                }

                if (isset($params[Param::MEMBER_ID]) && !empty($params[Param::MEMBER_ID])) {
                    $collection->addFieldToFilter('member_id', ['eq' => $params[Param::MEMBER_ID]]);
                }

                if (isset($params[Param::PURCHASE_ID]) && !empty($params[Param::PURCHASE_ID])) {
                    $collection->addFieldToFilter('purchase_id', ['eq' => $params[Param::PURCHASE_ID]]);
                }
                $collection
                    ->getSelect()
                    ->where(new \Zend_Db_Expr($purchasedDataFilter . " OR " . $returnDataFilter));

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
                        "transactionType" => ($transactionType == Param::TRANSACTION_TYPE_2) ? Param::TRANSACTION_TYPE_1 : $transactionType,
                        "transactionDateTime" => $this->posHelper->convertTimezone($purchaseTransactionDateTime, "UTC", "YmdHis"),
                        "totalAmount" => (float)$receipt->getTotalAmount(),
                        "discountAmount" => (float)$receipt->getDiscountAmount(),
                        "totalTax" => (float)$receipt->getTaxAmount(),
                        "memberId" => $receipt->getMemberId() ?? '',
                        "countryCode" => $receipt->getCountryCode() ?? '',
                        "usedPoint" => $receipt->getUsedPoint(),
                        "addedPoint" => $receipt->getAddedPoint(),
                        "pointTransactionDateTime" => $addedPointTransactionDateTime,
                        "pointHistoryId" => $receipt->getPointHistoryId() ?? '',
                    ];

                    if (strtotime($receipt->getPurchaseDate()) >= strtotime($startDateTime) &&
                        strtotime($receipt->getPurchaseDate()) <= strtotime($endDatetime)) {
                        $data[] = $purchasedData;
                    }

                    /*if ($transactionType == Param::TRANSACTION_TYPE_1) {
                        $data[] = $purchasedData;
                    }*/

                    if ($transactionType == Param::TRANSACTION_TYPE_2 &&
                        strtotime($receipt->getReturnDate()) >= strtotime($startDateTime) &&
                        strtotime($receipt->getReturnDate()) <= strtotime($endDatetime)) {
                        $prevData = $purchasedData;
                        $data[] = [
                            "purchaseId" => $prevData["purchaseId"],
                            "returnId" => $returnPurchaseId,
                            "terminalNo" => $prevData["terminalNo"],
                            "receiptNo" => $prevData["receiptNo"],
                            "transactionType" => Param::TRANSACTION_TYPE_2,
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
     * @param string $start
     * @param string $end
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
            if (is_bool($start) || is_bool($end)) {
                return Result::INVALID_SPECIFIED_PERIOD;
            }
            $diff = (int)date_diff($start, $end)->format('%R%a');
            if ($start > $end || $diff >= self::MAX_PERIOD_TIME) {
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
