<?php

namespace Clickend\Kerry\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
	const API_URL = "https://exch.th.kex-express.com/ediwebapi/SmartEDI/shipment_info";
	//const API_TEST_URL = "https://magento-334738-1058445.cloudwaysapps.com/response_api.php" ;
	const API_TEST_URL = "https://poc-it.th.kex-express.com/ediwebapi_uat/SmartEDI/shipment_info";

	//const API_STATUS_URL = "https://magento-334738-1058445.cloudwaysapps.com/response_api.php" ;//"http://exch.th.kerryexpress.com/ediwebapi_uat/SmartEDI/shipment_status";

	const XML_PATH_ENABLED = 'general/enable';
	const XML_PATH_TEST = 'general/mode';
	const XML_PATH_MARKET_NAME = 'general/market_name';
	const XML_PATH_VENDOR_NAME = 'general/vendor_name';
	const XML_PATH_PASSWORD = 'general/password';

	 protected $orderRepository;
    protected $searchCriteriaBuilder;


//	const XML_PATH_KERRY = 'kerry/';
    /**
     * @var \OnitsukaTiger\Logger\Kerry\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct( \Magento\Framework\App\Helper\Context $context,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig ,
			\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
			\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
             \OnitsukaTiger\Logger\Kerry\Logger $logger
    ) {
			 $this->orderRepository = $orderRepository;
        	 $this->searchCriteriaBuilder = $searchCriteriaBuilder;
             parent::__construct($context);
			$this->_scopeConfig = $scopeConfig;
			$this->logger = $logger;
    }

	public function logger($msg){
        $this->logger->info($msg);
	}

	public function getOrderById($id) {
        return $this->orderRepository->get($id);
    }

    public function getOrderByIncrementId($incrementId) {
        $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId);

        $order = $this->orderRepository
                      ->getList($this->searchCriteriaBuilder->create())
                      ->getItems();

        return $order;
    }

	public function getConfigValue($field)
	{
		return $this->scopeConfig->getValue(
            "kerry/".$field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
	}

	public function _isEnabled()
	{
		return $this->getConfigValue(self::XML_PATH_ENABLED);
	}
	public function _isTestMode()
	{
		return $this->getConfigValue(self::XML_PATH_TEST);
	}
	public function getPassword()
	{
		return $this->getConfigValue(self::XML_PATH_PASSWORD);
	}
	public function getMarketName()
	{
		return $this->getConfigValue(self::XML_PATH_MARKET_NAME);
	}
	public function getApiUrl()
	{
		if($this->_isTestMode())
			return self::API_TEST_URL;
		else
			return self::API_URL;
	}


	public function secureData($data = array()) {
		$marketName= $this->getConfigValue(self::XML_PATH_MARKET_NAME);
		$vendorName= $this->getConfigValue(self::XML_PATH_VENDOR_NAME);
		$password = $this->getPassword();
		if(!$marketName || !$vendorName || !$password)
			$this->logger("MarketName or VendorName or Password is not set by admin");
		$data['market'] = $marketName;
		$data['app_id'] = $vendorName;
		$data['app_key'] = $password;
		$data['api_version'] = '0.1';
		return $data;
	}

	public function postCall($data=array())
	{

		$secureData = $this->secureData();
		$ch = curl_init($this->getApiUrl());
		$data_string = json_encode($data, JSON_UNESCAPED_UNICODE);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Content-Type:application/json; charset=UTF-8',
						'Content-Length:'.strlen($data_string),
						'app_id:'.$secureData['app_id'],
						'app_key:'.$secureData['app_key']
		));

		$responses = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$result=json_decode($responses,true);
		$this->logger("=======================================================");
		$this->logger("Request Data: ".$data_string);
		$this->logger("Head: ".json_encode($secureData));
		$this->logger("Body: ".json_encode($result));
		$this->logger("=======================================================");
		return  $result;
	}


	public function placeOrder($orderid,$action_code)
	{

		$SenderName=$this->getConfigValue('vendor_information/sender_name');
		$SenderAddress=$this->getConfigValue('vendor_information/sender_address');
		$SenderVillage=$this->getConfigValue('vendor_information/sender_village');
		$SenderSoi=$this->getConfigValue('vendor_information/sender_soi');
		$SenderRoad=$this->getConfigValue('vendor_information/sender_road');
		$SenderSubDistrict=$this->getConfigValue('vendor_information/sender_sub_district');
		$SenderDistrict=$this->getConfigValue('vendor_information/sender_district');
		$SenderProvince=$this->getConfigValue('vendor_information/sender_province');
		$SenderZipcode=$this->getConfigValue('vendor_information/sender_zipcode');
		$SenderMobile1=$this->getConfigValue('vendor_information/sender_mobile_1');
		$SenderMobile2=$this->getConfigValue('vendor_information/sender_mobile_2');
		$SenderTelephone=$this->getConfigValue('vendor_information/sender_telephone');
		$SenderEmail=$this->getConfigValue('vendor_information/sender_email');
		$SenderContactPerson=$this->getConfigValue('vendor_information/sender_contact_person');




		if(!$orderid){
			$this->logger('Order ID not found');
			return false;
		}
		//$order = Mage::getModel('sales/order')->load($orderid);
		$order=$this->getOrderById($orderid);
		$orderItems = $order->getAllItems();
		$payment = $order->getPayment();
		$billingAddress = $order->getBillingAddress();
		$shippingAddress = $order->getShippingAddress();




		$marketName= $this->getConfigValue(self::XML_PATH_MARKET_NAME);

	//	if($awb && $action_code=="D"){
	//		$con_no = $awb;
	//	}else{
			$con_no = $marketName.$order->getIncrementId();
	//	}





		$payment_method_code = $payment->getMethod();
		$length = "10";
		$breadth = "10";
		$height = "10";
		$declaredValue = '0';
		$productDetails = '';
		if($payment_method_code == 'cashondelivery'){
			$paymentType = 'COD';
			$codAmount = $order->getGrandTotal();
		}
		else{
			$paymentType = 'CASH';
			$codAmount = '0';
		}
		$goWeight = 0;
		foreach ($order->getAllItems() as $item) {
			$goWeight += $item->getWeight();
			$productDetails .= $item->getName() . ' - ' . number_format($item->getQtyOrdered()) . ', ';
		}



		$s_phone = str_replace("-","",$SenderTelephone);
		$s_phone = str_replace(" ","",$s_phone);

		$r_phone = str_replace("-","",$shippingAddress->getTelephone());
		$r_phone = str_replace(" ","",$r_phone);



		$uri = null;
		$data = array();
		$data["req"]["shipment"] = array(

				"con_no" => $con_no,
				// ผู้ส่ง
				"s_name"=> $SenderName,
				"s_address"=> $SenderAddress,
				"s_village"=> $SenderVillage,
				"s_soi"=> $SenderSoi,
				"s_road"=> $SenderRoad,
				"s_subdistrict"=> $SenderSubDistrict,
				"s_district"=> $SenderDistrict,
				"s_province"=> $SenderProvince,
				"s_zipcode"=> $SenderZipcode,
				"s_mobile1"=> $s_phone,
				"s_mobile2"=> "",
				"s_telephone"=>  $s_phone,
				"s_email"=> $SenderEmail,
				"s_contactperson"=> $SenderContactPerson,

				// ผู้รับ
				"r_name"=> $shippingAddress->getFirstname()."  ".$shippingAddress->getLastname(),
				"r_address"=> $shippingAddress->getStreet(0)[0],
				"r_village"=> "",
				"r_soi"=> "",
				"r_road"=> "",
				"r_subdistrict"=> $shippingAddress->getSubdistrictName(),
				"r_district"=> $shippingAddress->getCity(),
				"r_province"=> $shippingAddress->getRegion(),
				"r_zipcode"=> (int)$order->getShippingAddress()->getPostcode(),
				"r_mobile1"=> $r_phone,
				"r_mobile2"=> "",
				"r_telephone"=> $r_phone,
				"r_email"=> $shippingAddress->getEmail(),
				"r_contactperson"=> $shippingAddress->getFirstname()."  ".$shippingAddress->getLastname()." ".$r_phone,


				"special_note"=> $payment_method_code,
				"service_code"=> "ND",
				"cod_amount"=> $codAmount,
				"cod_type"=> $paymentType,
				"tot_pkg"=> 1,
				"declare_value"=> $declaredValue,
				"ref_no"=> "REF-".sprintf('%010d', $order->getIncrementId()),
				"unique_id" => $order->getIncrementId(),
				"action_code"=> $action_code
		);


		$log = "";
		foreach($data["req"]["shipment"] as $key => $value){
			$log .=  "- ".$key." = ".$value."<br/>";
		}
		//$this->logger($log);
		//$order->addStatusHistoryComment($log);
		//$order->save();

		#print_r($data["req"]["shipment"]);exit();
		$return_data['request']=$data;
		$return_data['response']=$this->postCall($data);

		return  $return_data;
	}


}
