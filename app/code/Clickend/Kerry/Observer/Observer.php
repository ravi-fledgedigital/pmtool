<?php 
namespace Clickend\Kerry\Observer; 
use Exception;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class Observer implements ObserverInterface  { 
	
	protected $dataHelper;
	protected $resourceConnection;

	protected $_shipmentTrackFactory;
	protected $_shipmentFactory;
	protected $_transactionFactory;
	protected $_orderRepository;
	
 public function __construct(       
        \Clickend\Kerry\Helper\Data $dataHelper,	 
	    \Magento\Framework\App\ResourceConnection $resourceConnection ,	 
	 	\Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackFactory,
    	\Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
    	\Magento\Framework\DB\TransactionFactory $transactionFactory,
    	\Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
		$this->dataHelper = $dataHelper; 	 
		$this->resourceConnection = $resourceConnection;
	  	$this->_shipmentTrackFactory = $shipmentTrackFactory;
      	$this->_shipmentFactory = $shipmentFactory;
      	$this->_transactionFactory = $transactionFactory;
      	$this->_orderRepository = $orderRepository;
    }	
	
	
    public function execute(\Magento\Framework\Event\Observer $observer) { 
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Order.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		
		$helper = $this->dataHelper;
		
		$logger->info("Module Enable : ".$helper->_isEnabled());
		
		
		if($helper->_isEnabled()){
		
		$order = $observer->getEvent()->getOrder();
		
        $orderId = $order->getId();
		$customerId = $order->getCustomerId();
		$incrementId = $order->getIncrementId();
		$shipping= $order->getShippingMethod();
		
		$logger->info("=================== Order : ".$orderId." Event : ".$order->getState()."  =================");
		
		if($shipping=="kerryshipping_kerryshipping" && $order->getState()=="processing"){
			
			$get_data=$helper->placeOrder($orderId,'A');
			$logger->info("Con No : ".$get_data['response']['res']['shipment']['con_no']);
			$logger->info("Status Code : ".$get_data['response']['res']['shipment']['status_code']);
			$logger->info("Status Desc : ".$get_data['response']['res']['shipment']['status_desc']);			
			
					
			
			if($get_data['response']['res']['shipment']['status_code']=="000"){
				
			 try {		
				$logger->info("Insert Shipping Data");
				
				$con_no=$get_data['request']['req']['shipment']['con_no'];
				
				// ผู้ส่ง 
				$s_name=$get_data['request']['req']['shipment']['s_name'];
				$s_address=$get_data['request']['req']['shipment']['s_address'];
				$s_village=$get_data['request']['req']['shipment']['s_village'];
				$s_soi=$get_data['request']['req']['shipment']['s_soi'];
				$s_road=$get_data['request']['req']['shipment']['s_road'];
				$s_subdistrict=$get_data['request']['req']['shipment']['s_subdistrict'];
				$s_district=$get_data['request']['req']['shipment']['s_district'];
				$s_province=$get_data['request']['req']['shipment']['s_province'];				
				$s_zipcode=$get_data['request']['req']['shipment']['s_zipcode'];
				$s_mobile1=$get_data['request']['req']['shipment']['s_mobile1'];
				$s_mobile2=$get_data['request']['req']['shipment']['s_mobile2'];
				$s_telephone=$get_data['request']['req']['shipment']['s_telephone'];
				$s_email=$get_data['request']['req']['shipment']['s_email'];
				$s_contactperson=$get_data['request']['req']['shipment']['s_contactperson'];
				
				//$sender=$s_name." | ".$s_address." | ".$s_village." | ".$s_soi." | ".$s_road." | ".$s_subdistrict." | ".$s_district." | ".$s_province." | ".$s_zipcode." | ".$s_mobile1." | ".$s_mobile2." | ".$s_telephone." | ".$s_email." | ".$s_contactperson;
								
				// ผู้รับ 
				$r_name=$get_data['request']['req']['shipment']['r_name'];
				$r_address=$get_data['request']['req']['shipment']['r_address'];
				$r_village=$get_data['request']['req']['shipment']['r_village'];
				$r_soi=$get_data['request']['req']['shipment']['r_soi'];
				$r_road=$get_data['request']['req']['shipment']['r_road'];
				$r_subdistrict=$get_data['request']['req']['shipment']['r_subdistrict'];
				$r_district=$get_data['request']['req']['shipment']['r_district'];
				$r_province=$get_data['request']['req']['shipment']['r_province'];
				$r_zipcode=$get_data['request']['req']['shipment']['r_zipcode'];
				$r_mobile1=$get_data['request']['req']['shipment']['r_mobile1'];
				$r_mobile2=$get_data['request']['req']['shipment']['r_mobile2'];
				$r_telephone=$get_data['request']['req']['shipment']['r_telephone'];
				$r_email=$get_data['request']['req']['shipment']['r_email'];
				$r_contactperson=$get_data['request']['req']['shipment']['r_contactperson'];
				
				$special_note=$get_data['request']['req']['shipment']['special_note'];
				$service_code=$get_data['request']['req']['shipment']['service_code'];
				$cod_amount=$get_data['request']['req']['shipment']['cod_amount'];
				$cod_type=$get_data['request']['req']['shipment']['cod_type'];
				$tot_pkg=$get_data['request']['req']['shipment']['tot_pkg'];
				$declare_value=$get_data['request']['req']['shipment']['declare_value'];
				$ref_no=$get_data['request']['req']['shipment']['ref_no'];
				$unique_id=$get_data['request']['req']['shipment']['unique_id'];
				$action_code=$get_data['request']['req']['shipment']['action_code'];
				
				
                $sql = "INSERT INTO  kerry_shipping_track(con_no, unique_id, s_name, s_address, s_village, s_soi, s_road, s_subdistrict, s_district, s_province, s_zipcode, s_mobile1, s_mobile2, s_telephone, s_email, s_contact, r_name, r_address, r_village, r_soi, r_road, r_subdistrict, r_district, r_province, r_zipcode, r_mobile1, r_mobile2, r_telephone, r_email, r_contact, special_note, service_code, cod_amount, cod_type, tot_pkg, declare_value, ref_no, action_code) values ('".$con_no."', '".$unique_id."', '".$s_name."', '".$s_address."', '".$s_village."', '".$s_soi."', '".$s_road."', '".$s_subdistrict."', '".$s_district."', '".$s_province."', '".$s_zipcode."', '".$s_mobile1."', '".$s_mobile2."', '".$s_telephone."', '".$s_email."', '".$s_contactperson."', '".$r_name."', '".$r_address."', '".$r_village."', '".$r_soi."', '".$r_road."', '".$r_subdistrict."', '".$r_district."', '".$r_province."', '".$r_zipcode."', '".$r_mobile1."', '".$r_mobile2."', '".$r_telephone."', '".$r_email."', '".$r_contactperson."', '".$special_note."', '".$service_code."', '".$cod_amount."', '".$cod_type."', '".$tot_pkg."', '".$declare_value."', '".$ref_no."', '".$action_code."')";
				
				//$a=$get_data['request']['req']['shipment'][r_email];
				$logger->info($get_data['request']);
				
				$connection = $this->resourceConnection->getConnection()->query($sql);
				 
				$sqlStatus="INSERT INTO kerry_shipping_track_history (con_no,order_id,status,description,service_code,create_time,update_time)";
				$sqlStatus.="VALUES ('".$con_no."', '".$unique_id."', 'New Shipping', 'Successfully', '000','".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."')";
				 
				 
				$recon=$this->resourceConnection->getConnection()->query($sqlStatus); 
				 
				 
				$this->createShipment($orderId,$con_no);
				 
				} catch (Exception $e) {
					
				    $message = "ERROR Cannot insert Trackking \n".$e->getMessage();					
 					throw new CouldNotSaveException(__($message));
 					return false;
					
				 } 
				 
				 
				 
			}else{
				$logger->info("ERROR Can't add tracking [ Error Code : ".$get_data['response']['res']['shipment']['status_code']." ] | [ Description : ".$get_data['response']['res']['shipment']['status_desc']." ]");
				
				$message = "ERROR Can't add tracking [ Error Code : ".$get_data['response']['res']['shipment']['status_code']." ] | [ Description : ".$get_data['response']['res']['shipment']['status_desc']." ]";
				
				throw new CouldNotSaveException(__($message));
                return false;
			}
				 
			
				 
				 
		
		  }
		}//return $this;
    }
	
	
	protected function createShipment($orderId, $trackingNumber)
{
	
	$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Order.log');
	$logger = new \Zend\Log\Logger();
	$logger->addWriter($writer);
	
    try {
        $order = $this->_orderRepository->get($orderId);
        if ($order){
            $data = array(array(
                'carrier_code' => $order->getShippingMethod(),
                'title' => "Kerry Express",
                'number' => $trackingNumber,
            ));
            $shipment = $this->prepareShipment($order, $data);
            if ($shipment) {
                $order->setIsInProcess(true);
                $order->addStatusHistoryComment('A delivery tracking number was generated.', false);
                $transactionSave =  $this->_transactionFactory->create()->addObject($shipment)->addObject($shipment->getOrder());
                $transactionSave->save();
				
				$logger->info("Trackking Added".print_r($data,true));
				
            }
            return $shipment;
        }
    } catch (\Exception $e) {      
		throw new CouldNotSaveException(__($e->getMessage()));
        return false;
    }
}
 
/**
* @param $order \Magento\Sales\Model\Order
* @param $track array
* @return $this
*/
protected function prepareShipment($order, $track)
{
   $shipment = $this->_shipmentFactory->create(
       $order,
       $this->prepareShipmentItems($order),
       $track
   );
   return $shipment->getTotalQty() ? $shipment->register() : false;
}
 
/**
* @param $order \Magento\Sales\Model\Order
* @return array
*/
protected function prepareShipmentItems($order)
{
   $items = [];
 
   foreach($order->getAllItems() as $item) {
       $items[$item->getItemId()] = $item->getQtyOrdered();
   }
   return $items;
}
	
	
}

?>