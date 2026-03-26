<?php

namespace OnitsukaTigerCpss\Crm\Model\Shop;

use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\Shop\Config\DB as DBField;
use Cpss\Crm\Model\Shop\Config\Result;
use Cpss\Crm\Model\Shop\DeleteReceipt as CrmDeleteReceipt;
use Cpss\Crm\Model\ShopReceiptFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\Website;
use OnitsukaTigerCpss\Crm\Helper\MemberValidation;
use OnitsukaTigerCpss\Crm\Helper\Validation;
use OnitsukaTigerCpss\Crm\Model\Shop\Config\Param;

class DeleteReceipt extends CrmDeleteReceipt
{
    protected $request;
    protected $validation;
    protected $logger;
    protected $shopReceiptFactory;
    protected Website $website;

    public function __construct(
        RequestInterface   $request,
        Validation         $validation,
        Logger             $logger,
        ShopReceiptFactory $shopReceiptFactory,
        Website               $website
    )
    {
        $this->request = $request;
        $this->validation = $validation;
        $this->logger = $logger;
        $this->shopReceiptFactory = $shopReceiptFactory;
        $this->website = $website;
        parent::__construct($request, $validation, $logger, $shopReceiptFactory);
    }

    /**
     * execute delete registered receipt(from store) info API
     *
     * @return string
     */
    public function execute()
    {
        $this->logger->info("rest/V1/deleteShopReceipt");
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
                $resultCode = $this->validation->validateParams(Param::KR_REQUEST_DELETE_PARAMS, $params);
            } else {
                $resultCode = $this->validation->validateParams(Param::REQUEST_DELETE_PARAMS, $params);
            }

            //validate for SG
            if ($resultCode === $success) {
                /*if (!$website->getId() ||
                    (!empty($website->getId()) && MemberValidation::WEBSITE_COUNTRY_CODE[$website->getId()] !== "SG")) {*/
                if (!$website || !$website->getId()) {
                    $resultCode = Result::AUTH_FAILED;
                    $this->logger->critical(__('Website is not found or %1', Result::RESULT_CODES[$resultCode]));
                    $response = [
                        'resultCode' => $resultCode,
                        'resultExplanation' => Result::RESULT_CODES[$resultCode],
                        "pointHistoryId" => ""
                    ];
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($response);
                    exit;
                }
            }

            if ($resultCode === $success) {
                $resultCode = ($this->validation->validateAccessToken($params[Param::SHOP_ID], $params[Param::ACCESS_TOKEN])) ? Result::SUCCESS : Result::AUTH_FAILED;
            }

            if ($resultCode === $success) {
                $deleteResult = $this->processDeletion($params[Param::PURCHASE_ID]);
                $resultCode = $deleteResult['result'];
            }

            $resultExplanation = Result::RESULT_CODES[$resultCode];

            $result = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation,
                "pointHistoryId" =>""
            ];

            if ($resultCode === $success) {
                $result['pointHistoryId'] = $deleteResult[Param::POINT_HISTORY_ID];
            }
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
     * process the deletion of the receipt
     * @param $purchaseId
     * @return array
     */
    public function processDeletion($purchaseId)
    {
        $result = [
            'result' => Result::INTERNAL_ERROR
        ];
        try {
            $model = $this->shopReceiptFactory->create();
            $model->load($purchaseId, DBField::PARAM[Param::PURCHASE_ID]);
            if (!$model->getId() || $model->getData('return_date')) {
                $result['result'] = Result::RECEIPT_NOT_REGISTERED;
                return $result;
            }

            $pointHistoryId = $model->getPointHistoryId() ? $model->getPointHistoryId()  : '' ;
            $model
                ->setId($model->getId())
                ->setOriginPurchaseId($purchaseId)
                ->setTransactionType(Param::TRANSACTION_TYPE_2)
                ->setReturnDate($this->validation->getDateTime())// ->setPointTransactionDateTime('xxxx')
            ;

            if ($model->save()) $this->logger->info(__('Deleted shop receipt successfully.'));

            $result = [
                'result' => Result::SUCCESS,
                'pointHistoryId' => $pointHistoryId
            ];

        } catch (\Exception $e) {
            $this->logger->error(__($e->getMessage()));
        }
        return $result;
    }
}
