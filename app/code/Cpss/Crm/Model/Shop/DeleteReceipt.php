<?php 

namespace Cpss\Crm\Model\Shop;

use Cpss\Crm\Model\Shop\Config\Result;
use Cpss\Crm\Model\Shop\Config\Param;
use Cpss\Crm\Api\Shop\DeleteReceiptInterface;
use Magento\Framework\App\RequestInterface;
use Cpss\Crm\Helper\Validation;
use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\ShopReceiptFactory;
use Cpss\Crm\Model\Shop\Config\DB as DBField;

class DeleteReceipt implements DeleteReceiptInterface
{
	protected $request;
	protected $validation;
	protected $logger;
	protected $shopReceiptFactory;

    public function __construct(
       RequestInterface $request,
       Validation $validation,
	   Logger $logger,
	   ShopReceiptFactory $shopReceiptFactory
    ) {
       $this->request = $request;
       $this->validation = $validation;
	   $this->logger = $logger;
	   $this->shopReceiptFactory = $shopReceiptFactory;
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
		try{
			$success = Result::SUCCESS;
			$params = $this->request->getParams();
			$this->logger->info("REQUEST: " . json_encode($params));

			$resultCode = $this->validation->validateParams(Param::DELETE_PARAMS, $params);
			if($resultCode === $success){
				$resultCode = ($this->validation->validateAccessToken($params[Param::SHOP_ID], $params[Param::ACCESS_TOKEN])) ?  Result::SUCCESS : Result::AUTH_FAILED;
			}

			if($resultCode === $success){
				$deleteResult = $this->processDeletion($params[Param::PURCHASE_ID]);
				$resultCode = $deleteResult['result'];
			}
	
			$resultExplanation = Result::RESULT_CODES[$resultCode];
	
			$result = [
				"resultCode" => $resultCode,
				"resultExplanation" => $resultExplanation
			];

			if($resultCode === $success){
				$result['pointHistoryId'] = $deleteResult[Param::POINT_HISTORY_ID];
			}
		}catch(\Exception $e){
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
     *
     * @param  string $purchaseId
     * @return string
     */
	public function processDeletion($purchaseId)
	{
		$result = [];
		try{
			$model = $this->shopReceiptFactory->create();
			$model->load($purchaseId, DBField::PARAM[Param::PURCHASE_ID]);
			if ($model->getId()){
				$pointHistoryId = $model->getPointHistoryId();

				$model
					->setId($model->getId())
					->setOriginPurchaseId($purchaseId)
					->setTransactionType(Param::TRANSACTION_TYPE_2)
					->setReturnDate($this->validation->getDateTime())
					// ->setPointTransactionDateTime('xxxx')
					;

				if ($model->save()) $this->logger->info(__('Deleted shop receipt successfully.'));

				return [
					'result' => Result::SUCCESS,
					'pointHistoryId' => $pointHistoryId
				];
			}else{
				return ['result' => Result::RECEIPT_NOT_REGISTERED];
			}
		}catch(\Exception $e){
			$this->logger->error(__($e->getMessage()));
			return ['result' => Result::INTERNAL_ERROR];
		}
	}
}
