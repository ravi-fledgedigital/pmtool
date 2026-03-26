<?php

namespace Cpss\Crm\Model\Shop;

use Cpss\Crm\Model\Shop\Config\Result;
use Cpss\Crm\Model\Shop\Config\Param;
use Cpss\Crm\Api\Shop\RegisterReceiptInterface;
use Magento\Framework\App\RequestInterface;
use Cpss\Crm\Helper\Validation;
use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\ShopReceiptFactory;
use Cpss\Crm\Model\Shop\Config\DB as DBField;
use Cpss\Crm\Model\CpssApiRequest;
use Cpss\Crm\Model\PointConfigProvider;

class RegisterReceipt implements RegisterReceiptInterface
{
    
    protected $request;
    protected $validation;
    protected $logger;
    protected $shopReceiptFactory;
    protected $currentPointHisId = "";
    protected $timezone;
    protected $posHelper;
    protected $pointConfigProvider;

    public function __construct(
        CpssApiRequest $cpssApiRequest,
        RequestInterface $request,
        Validation $validation,
        Logger $logger,
        ShopReceiptFactory $shopReceiptFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Cpss\Pos\Helper\Data $posHelper,
        PointConfigProvider $pointConfigProvider
    ) {
        $this->cpssApiRequest = $cpssApiRequest;
        $this->request = $request;
        $this->validation = $validation;
        $this->logger = $logger;
        $this->shopReceiptFactory = $shopReceiptFactory;
        $this->timezone = $timezone;
        $this->posHelper = $posHelper;
        $this->pointConfigProvider = $pointConfigProvider;
    }

    /**
     * execute register receipt(from store) info API
     *
     * @return string
     */
    public function execute()
    {
        $this->logger->info("rest/V1/registerShopReceipt");
        $result = [];
        try {
            $success = Result::SUCCESS;
            $params = $this->request->getParams();
            $this->logger->info("REQUEST: " . json_encode($params));

            $resultCode = $this->validation->validateParams(Param::REGISTER_PARAMS, $params);
            $resultCode = 0;
            if ($resultCode === Result::SUCCESS) {
                $resultCode = ($this->validation->validateAccessToken($params[Param::SHOP_ID], $params[Param::ACCESS_TOKEN])) ?
                    Result::SUCCESS :
                    Result::AUTH_FAILED;
            }
            $resultCode = 0;
            if ($resultCode === $success) {
                $resultCode = $this->processRegistration($params);
            }

            $resultExplanation = Result::RESULT_CODES[$resultCode];
            $pointHistoryId = $params[Param::POINT_HISTORY_ID] ?? '';
            if ($params[Param::TRANSACTION_TYPE] == Param::TRANSACTION_TYPE_2) {
                $pointHistoryId = $this->currentPointHisId;
            }
            
            $result = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation,
                "pointHistoryId" => ($resultCode === $success) ? $pointHistoryId : ""
            ];
        } catch (\Exception $e) {
            $this->logger->error(__($e->getMessage()));
            $resultCode = Result::INTERNAL_ERROR;
            $resultExplanation = Result::RESULT_CODES[$resultCode];

            $result = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation,
                "pointHistoryId" => ""
            ];
        }

        $this->logger->info("RESPONSE: " . json_encode($result));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        exit;
    }

    /**
     * process the registration of the receipt
     *
     * @param  array $params
     * @return int
     */
    public function processRegistration($params)
    {
        try {
            $model = $this->shopReceiptFactory->create();
            $purchaseId = $params[Param::PURCHASE_ID];

            $purchaseIdArr = explode("_", $purchaseId);
            $date = date('Y-m-d H:i:s', strtotime($purchaseIdArr[0]));
            $countryCode = isset($params[Param::COUNTRY_CODE]) ? (int) $params[Param::COUNTRY_CODE] : "";

            $model->load($purchaseId, DBField::PARAM[Param::PURCHASE_ID]);
            if ($params[Param::TRANSACTION_TYPE] == "2") {
                if (!$model->getId()) {
                    return Result::RECEIPT_NOT_REGISTERED;
                }

                $model->setTransactionType($params[Param::TRANSACTION_TYPE] ?? "");
                if ($model->getReturnDate() == null) {
                    $model->setReturnDate($this->getUTCdate());
                }
                if ($model->save()) {
                    $this->currentPointHisId = $model->getPointHistoryId() ?? "";
                    $this->logger->info(__('Shop receipt transaction type updated to return/cancel.'));
                    return Result::SUCCESS;
                }
            }

            $acquiredPoints = isset($params[Param::MEMBER_ID]) ? $this->pointConfigProvider->calcAcquiredPointsForRealStore($purchaseId, $params[Param::MEMBER_ID]) : 0;
            $model
                ->setPurchaseId($purchaseId)
                ->setShopId($purchaseIdArr[1])
                ->setPosTerminalNo($purchaseIdArr[2])
                ->setReceiptNo($purchaseIdArr[3])
                ->setTransactionType(isset($params[Param::TRANSACTION_TYPE]) ? $params[Param::TRANSACTION_TYPE] : "")
                // ->setReturnCancelDatetime($params['xxxx'])
                // ->setTotalAmount($params['xxxx'])
                // ->setDiscountAmount($params['xxxx'])
                // ->setTotalTax($params['xxxx'])
                ->setMemberId(isset($params[Param::MEMBER_ID]) ? $params[Param::MEMBER_ID] : "")
                ->setCountryCode($countryCode)
                ->setAcquiredPoint($acquiredPoints)
                ->setUsedPoint(isset($params[Param::USED_POINT]) ? $params[Param::USED_POINT] : "")
                // ->setAddedPointDate($params['xxxx'])
                // ->setCancelPointDate($params['xxxx'])
                ->setPointHistoryId(isset($params[Param::POINT_HISTORY_ID]) ? $params[Param::POINT_HISTORY_ID] : "")
                ->setGuestPurchaseFlg(isset($params[Param::MEMBER_ID]) ? 0 : 1);

            if ($params[Param::TRANSACTION_TYPE] == 1 && empty($model->getPurchaseDate())) {
                //purchase
                $date = $this->posHelper->convertTimezone($date, "UTC", "Y-m-d H:i:s");
                $model->setPurchaseDate($date);
            }

            if ($model->getId()) {
                $model->setId($model->getId());
            }
            if ($model->save()) {
                $this->logger->info(__('Registered shop receipt successfully.'));
            }

            return Result::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->critical(__($e->getMessage()));
            return Result::INTERNAL_ERROR;
        }
    }

    protected function getUTCdate()
    {
        //Convert time to UTC
        $utcFormat = "Y-m-d H:i:s";
        $dateNow = $this->posHelper->convertTimezone(
            $this->timezone->date()->format("Y-m-d 00:00:00"), //JST
            "UTC",
            $utcFormat
        );
        
        return $dateNow;
    }
}
