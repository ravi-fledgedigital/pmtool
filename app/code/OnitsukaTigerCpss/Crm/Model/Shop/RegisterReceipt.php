<?php

namespace OnitsukaTigerCpss\Crm\Model\Shop;

use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\CpssApiRequest;
use Cpss\Crm\Model\PointConfigProvider;
use Cpss\Crm\Model\Shop\Config\DB as DBField;
use Cpss\Crm\Model\Shop\Config\Result;
use Cpss\Crm\Model\Shop\RegisterReceipt as CrmRegisterReceipt;
use Cpss\Crm\Model\ShopReceiptFactory;
use Cpss\Pos\Helper\Data;
use League\ISO3166\Exception\DomainException;
use League\ISO3166\Exception\OutOfBoundsException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\Website;
use OnitsukaTigerCpss\Crm\Helper\Data as CustomerHelper;
use OnitsukaTigerCpss\Crm\Helper\MemberValidation;
use OnitsukaTigerCpss\Crm\Helper\Validation;
use OnitsukaTigerCpss\Crm\Model\Shop\Config\Param;

class RegisterReceipt extends CrmRegisterReceipt
{
    protected $cpssApiRequest;
    protected $request;
    protected $validation;
    protected $logger;
    protected $shopReceiptFactory;
    protected $currentPointHisId = "";
    protected $timezone;
    /**
     * @var Data
     */
    protected $posHelper;
    /**
     * @var PointConfigProvider
     */
    protected $pointConfigProvider;
    /**
     * @var CustomerHelper
     */
    protected CustomerHelper $crmHelper;
    /**
     * @var Website
     */
    protected Website $website;
    public function __construct(
        CpssApiRequest      $cpssApiRequest,
        RequestInterface    $request,
        Validation          $validation,
        Logger              $logger,
        ShopReceiptFactory  $shopReceiptFactory,
        TimezoneInterface   $timezone,
        Data                $posHelper,
        PointConfigProvider $pointConfigProvider,
        Website             $website,
        CustomerHelper      $crmHelper
    ) {
        $this->cpssApiRequest = $cpssApiRequest;
        $this->request = $request;
        $this->validation = $validation;
        $this->logger = $logger;
        $this->shopReceiptFactory = $shopReceiptFactory;
        $this->timezone = $timezone;
        $this->posHelper = $posHelper;
        $this->pointConfigProvider = $pointConfigProvider;
        $this->website = $website;
        $this->crmHelper = $crmHelper;
        parent::__construct(
            $cpssApiRequest,
            $request,
            $validation,
            $logger,
            $shopReceiptFactory,
            $timezone,
            $posHelper,
            $pointConfigProvider
        );
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
            $website = false;
            if (isset($params[Param::SITE_ID])) {
                $website = $this->website->load($params[Param::SITE_ID]);
            }
            $this->logger->info("REQUEST: " . json_encode($params));

            if ($website && $website->getId() && $website->getCode() == 'web_kr') {
                $resultCode = $this->validation->validateParams(Param::KR_REQUEST_REGISTER_PARAMS, $params);
            } else {
                $resultCode = $this->validation->validateParams(Param::REQUEST_REGISTER_PARAMS, $params);
            }

            if ($resultCode === Result::SUCCESS) {
                $resultCode = ($this->validation->validateAccessToken($params[Param::SHOP_ID], $params[Param::ACCESS_TOKEN])) ?
                    Result::SUCCESS :
                    Result::AUTH_FAILED;
            }
            //validate Country ID
            if ($resultCode === Result::SUCCESS && !empty($params[Param::COUNTRY_CODE]) && !$this->validateCountryId($params[Param::COUNTRY_CODE])) {
                $resultCode =  Result::INVALID_PARAMS;
                $this->logger->critical(__('CountryCode is not found or %1', Result::RESULT_CODES[$resultCode]));
            }
            //optional for special transaction type 2
            if (isset($params[Param::TRANSACTION_TYPE]) && $params[Param::TRANSACTION_TYPE] == Param::TRANSACTION_TYPE_2) {
                $validSpecialType = empty($params[Param::MEMBER_ID]) && empty($params[Param::COUNTRY_CODE])
                 && empty($params[Param::USED_POINT])  &&empty($params[Param::POINT_HISTORY_ID]);
                $resultCode = $validSpecialType ? Result::SUCCESS : $resultCode;
            }

            if ($resultCode === $success) {
                //validate for SG

                /*if (!$website->getId() ||
                    (!empty($website->getId()) && MemberValidation::WEBSITE_COUNTRY_CODE[$website->getId()] !== "SG")
                    && in_array($params[Param::COUNTRY_CODE], MemberValidation::WEBSITE_COUNTRY_ID)) {*/ // allow for SG site
                if (!$website || !$website->getId()) {
                    $resultCode = Result::AUTH_FAILED;
                    $this->logger->critical(__('Website is not found or %1', Result::RESULT_CODES[$resultCode]));
                    $response = [
                        'resultCode' => $resultCode,
                        'resultExplanation' => Result::RESULT_CODES[$resultCode],
                        "pointHistoryId" =>""
                    ];
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($response);
                    exit;
                }

                //validate member
                if (!empty($params[Param::MEMBER_ID])) {
                    $customer = $this->crmHelper->getCustomerFactory();
                    $customer = $customer->load($params[Param::MEMBER_ID]);
                    if ($customer && $customer->getWebsiteId() !== $params[Param::SITE_ID]) {
                        $resultCode = Result::INVALID_PARAMS;
                        $this->logger->critical(__('Member is invalid %1', Result::RESULT_CODES[$resultCode]));
                        $response = [
                            'resultCode' => $resultCode,
                            'resultExplanation' => Result::RESULT_CODES[$resultCode],
                            "pointHistoryId" =>""
                        ];
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode($response);
                        exit;
                    }
                }
                $resultCode = $this->processRegistration($params);
                $modelOrder = $this->shopReceiptFactory->create();
                $modelOrder =  $modelOrder->load($params[Param::PURCHASE_ID], DBField::PARAM[Param::PURCHASE_ID]);
                $this->currentPointHisId = $modelOrder ? $modelOrder->getData('point_history_id') : '';
            }
            $resultExplanation = Result::RESULT_CODES[$resultCode];
            $result = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation,
                "pointHistoryId" => ($resultCode === $success) ? $this->currentPointHisId : ""
            ];
        } catch (DomainException $e) {
            $resultCode =  Result::INVALID_PARAMS;
            $result = [
                "resultCode" => $resultCode,
                "resultExplanation" =>  Result::RESULT_CODES[$resultCode],
                "pointHistoryId" => ""
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
     * @param array $params
     * @return int
     */
    public function processRegistration($params)
    {
        try {
            $model = $this->shopReceiptFactory->create();
            $purchaseId = $params[Param::PURCHASE_ID];

            $purchaseIdArr = explode("_", $purchaseId);
            $time = date('H:i:s');
            /*$date = date('Y-m-d H:i:s', strtotime("$purchaseIdArr[0] $time"));*/
            $date = date('Y-m-d H:i:s');

            $countryCode = isset($params[Param::COUNTRY_CODE]) ? (int)$params[Param::COUNTRY_CODE] : "";
            $model->load($purchaseId, DBField::PARAM[Param::PURCHASE_ID]);
            if ($params[Param::TRANSACTION_TYPE] == Param::TRANSACTION_TYPE_2) {
                if (!$model->getId()) {
                    return Result::RECEIPT_NOT_REGISTERED;
                }

                $model->setTransactionType($params[Param::TRANSACTION_TYPE] ?? "");
                if ($model->getReturnDate() == null) {
                    $model->setReturnDate($this->getUTCdate());
                }
                if (!empty($params[Param::MEMBER_ID])) {
                    $model->setMemberId($params[Param::MEMBER_ID]);
                }
                if ($model->save()) {
                    $this->currentPointHisId = $model->getPointHistoryId() ?? "";
                    $this->logger->info(__('Shop receipt transaction type updated to return/cancel.'));
                    return Result::SUCCESS;
                }
            }

            $acquiredPoints = 0;
            if ($model->getId()) {
                $acquiredPoints = isset($params[Param::MEMBER_ID]) ? $this->pointConfigProvider->calcAcquiredPointsForRealStore($purchaseId, $params[Param::MEMBER_ID]) : 0;
            }

            $storeCode = strtolower(MemberValidation::WEBSITE_COUNTRY_CODE[$params[Param::SITE_ID]]);

            $model
                ->setPurchaseId($purchaseId)
                ->setShopId($purchaseIdArr[1])
                ->setPosTerminalNo($purchaseIdArr[2])
                ->setReceiptNo($purchaseIdArr[3])
                ->setTransactionType(isset($params[Param::TRANSACTION_TYPE]) ? $params[Param::TRANSACTION_TYPE] : "")
                ->setStoreCode($storeCode)
                // ->setReturnCancelDatetime($params['xxxx'])
                // ->setTotalAmount($params['xxxx'])
                // ->setDiscountAmount($params['xxxx'])
                // ->setTotalTax($params['xxxx'])
                ->setMemberId(isset($params[Param::MEMBER_ID]) ? $params[Param::MEMBER_ID] : "");
            if (!empty($countryCode)) {
                $model->setCountryCode($countryCode);
            }
            if (!empty($acquiredPoints)) {
                $model->setAcquiredPoint($acquiredPoints);
            }

            if (isset($params[Param::USED_POINT])) {
                $model->setUsedPoint(isset($params[Param::USED_POINT]) ? $params[Param::USED_POINT] : "");
            }
            if (isset($params[Param::POINT_HISTORY_ID])) {
                $model->setPointHistoryId(isset($params[Param::POINT_HISTORY_ID]) ? $params[Param::POINT_HISTORY_ID] : "");
            }
            // ->setAddedPointDate($params['xxxx'])
            // ->setCancelPointDate($params['xxxx'])
            $model->setGuestPurchaseFlg(isset($params[Param::MEMBER_ID]) ? 0 : 1);

            if ($params[Param::TRANSACTION_TYPE] == Param::TRANSACTION_TYPE_1 && empty($model->getPurchaseDate())) {
                //purchase
                //$date = $this->posHelper->convertTimezone($date, "UTC", "Y-m-d H:i:s");
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
            return  Result::INTERNAL_ERROR;
        }
    }

    protected function getUTCdate()
    {
        //Convert time to UTC
        $utcFormat = "Y-m-d H:i:s";
        $dateNow = $this->posHelper->convertTimezone(
            date("Y-m-d H:i:s"), //JST
            "UTC",
            $utcFormat
        );

        return $dateNow;
    }
    /**
     * Validate the existence of a country ID
     *
     * @param string $countryId
     * @return bool
     */
    public function validateCountryId($countryId)
    {
        try {
            $country = $this->crmHelper->getCountryById($countryId);
            return $country !== null;
        } catch (OutOfBoundsException $e) {
            return false;
        }
    }
}
