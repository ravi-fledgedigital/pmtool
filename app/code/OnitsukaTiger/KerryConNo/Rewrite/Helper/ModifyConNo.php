<?php
namespace OnitsukaTiger\KerryConNo\Rewrite\Helper;

use Clickend\Kerry\Helper\Data;
use Magento\Sales\Model\Order\Shipment;

/**
 * Class ModifyConNo
 * @package OnitsukaTiger\KerryConNo\Rewrite\Helper
 */
class ModifyConNo extends Data
{
    /**
     * @var \Magento\InventoryApi\Api\SourceRepositoryInterface
     */
    protected $sourceRepository;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository
     * @param \OnitsukaTiger\Logger\Kerry\Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository,
        \OnitsukaTiger\Logger\Kerry\Logger $logger
    ) {
        $this->sourceRepository = $sourceRepository;
        parent::__construct($context, $scopeConfig, $orderRepository, $searchCriteriaBuilder, $logger);
    }

    /**
     * Preference parent placeOrder function
     *
     * @param Shipment $shipment
     * @param $action_code
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function placeOrder($shipment, $action_code)
    {
        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
        $source = $this->sourceRepository->get($sourceCode);

        $SenderName= $source->getContactName();
        $SenderAddress= $source->getStreet();
        $SenderVillage= '';
        $SenderSoi= '';
        $SenderRoad= '';
        $SenderSubDistrict= '';
        $SenderDistrict= '';
        $SenderProvince=$source->getCity();
        $SenderZipcode= $source->getPostcode();
        $SenderMobile1= $source->getPhone();
        $SenderMobile2= $source->getPhone();
        $SenderTelephone= $source->getPhone();
        $SenderEmail= $source->getEmail();
        $SenderContactPerson= $source->getName();

        if (!$shipment) {
            $this->logger('Shipment not found');
            return false;
        }
        //$order = Mage::getModel('sales/order')->load($orderid);
        $order = $shipment->getOrder();
        $orderItems = $order->getAllItems();
        $payment = $order->getPayment();
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        $marketName= $this->getConfigValue(self::XML_PATH_MARKET_NAME);

        $storeId = $order->getStoreId();
        $modifyShipmentIncrementId = substr($shipment->getIncrementId(), -7);

        //	if($awb && $action_code=="D"){
        //		$con_no = $awb;
        //	}else{
        $con_no = $marketName . $storeId . $modifyShipmentIncrementId;
        //	}

        $payment_method_code = $payment->getMethod();
        $length = "10";
        $breadth = "10";
        $height = "10";
        $declaredValue = '0';
        $productDetails = '';
        if ($payment_method_code == 'cashondelivery') {
            $paymentType = 'COD';
            $codAmount = $order->getGrandTotal();
        } else {
            $paymentType = 'CASH';
            $codAmount = '0';
        }
        $goWeight = 0;
        foreach ($shipment->getAllItems() as $item) {
            $goWeight += $item->getWeight();
            $productDetails .= $item->getName() . ' - ' . number_format($item->getQty()) . ', ';
        }

        $s_phone = str_replace("-", "", $SenderTelephone);
        $s_phone = str_replace(" ", "", $s_phone);

        $r_phone = str_replace("-", "", $shippingAddress->getTelephone());
        $r_phone = str_replace(" ", "", $r_phone);

        $uri = null;
        $data = [];
        $data["req"]["shipment"] = [

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
            "r_name"=> $shippingAddress->getFirstname() . "  " . $shippingAddress->getLastname(),
            "r_address" => $shippingAddress->getStreet(0)[0] . (isset($shippingAddress->getStreet(0)[1]) ? " " . $shippingAddress->getStreet(0)[1] : ''),
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
            "r_contactperson"=> $shippingAddress->getFirstname() . "  " . $shippingAddress->getLastname() . " " . $r_phone,

            "special_note"=> $payment_method_code,
            "service_code"=> "ND",
            "cod_amount"=> $codAmount,
            "cod_type"=> $paymentType,
            "tot_pkg"=> 1,
            "declare_value"=> $declaredValue,
            "ref_no"=> "REF-" . sprintf('%010d', $shipment->getIncrementId()),
            "unique_id" => $shipment->getIncrementId(),
            "action_code"=> $action_code
        ];

        $log = "";
        foreach ($data["req"]["shipment"] as $key => $value) {
            $log .=  "- " . $key . " = " . $value . "<br/>";
        }
        //$this->logger($log);
        //$order->addStatusHistoryComment($log);
        //$order->save();

        #print_r($data["req"]["shipment"]);exit();
        $return_data['request']=$data;
        $return_data['response']=$this->postCall($data);

        return  $return_data;
    }

    public function postCall($data=[])
    {
        $curl = curl_init();
        $data_string = json_encode($data, JSON_UNESCAPED_UNICODE);
        $secureData = $this->secureData();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->getApiUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data_string,
            CURLOPT_HTTPHEADER => [
                'app_key: ' . $secureData['app_key'],
                'app_id: ' . $secureData['app_id'],
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
            ],
        ]);

        $responses = curl_exec($curl);

        curl_close($curl);
        $result=json_decode($responses, true);
        $this->logger("=======================================================");
        $this->logger("Request Data: " . $data_string);
        $this->logger("Head: " . json_encode($secureData));
        $this->logger("Body: " . json_encode($result));
        $this->logger("=======================================================");
        return  $result;
    }
}
