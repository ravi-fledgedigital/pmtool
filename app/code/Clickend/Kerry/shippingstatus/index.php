<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
use Magento\Framework\App\Bootstrap;

require '../app/bootstrap.php';

$params = $_SERVER;

$bootstrap = Bootstrap::create(BP, $params);

$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');


$datas = file_get_contents('php://input');	
$data = json_decode($datas,true);


$helper = $objectManager->create('\Clickend\Kerry\Helper\Data');
$resource = $objectManager->create('\Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);

function addstatus($order_id,$title,$tracking,$desc) {
	 
	 $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	 $resource = $objectManager->create('\Magento\Framework\App\ResourceConnection');
	 $connection = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
     $order = $objectManager->create('Magento\Sales\Model\Order')->load($order_id);

    if ($order->canShip()) {
		
		
		$shipment_detail= $order->getShipmentsCollection();
		$shipmentId = 0;
		foreach ($shipment_detail as $shipment) {
			$shipmentId = $shipment->getId();
		}
		$order->addStatusHistoryComment('Shipping Status : ['.$desc.']', false);
		$order->save();
		
		$sqladdStatus="INSERT INTO sales_shipment_track (parent_id,order_id, track_number, description, title, carrier_code)";
		$sqladdStatus.="VALUES ('".$shipmentId."', '".$order_id."', '".$tracking."', '".$desc."', '".$title."', '".$order->getShippingMethod()."')";
		$connection->query($sqladdStatus);

    } else {
		//$logger->info("Shipment Not Created Because It's already created or something went wrong ");       
   }
}


	
		if($data['req']){
			$con_no=$data['req']['status']['con_no'];
			$status_code=$data['req']['status']['status_code'];
			$status_desc=$data['req']['status']['status_desc'];
			$status_date=$data['req']['status']['status_date'];
			$update_date=$data['req']['status']['update_date'];
			$ref_no=$data['req']['status']['ref_no'];
			
			if($data['req']['status']['location']){
				$location="( ".$data['req']['status']['location']." )";				
			}
			
			$ex_oreder_id=explode($helper->getMarketName(),$con_no);
			$status=$status_desc." ".$location; 				
			
			
			
			$check_con=$connection->fetchAll("select count(con_no) as num from kerry_shipping_track where con_no='".$con_no."'");
						
			
			
			if($check_con[0]['num']>=1){				
				$load_order=$objectManager->create('\Magento\Sales\Api\Data\OrderInterface');
				$order = $load_order->loadByIncrementId($ex_oreder_id[1]);
				
				if($status_code=="POD"){
					$newState = \Magento\Sales\Model\Order::STATE_COMPLETE;
					$order->setState($newState)->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
					$order->save();
				}
				
				$sql="INSERT INTO kerry_shipping_track_history (con_no,order_id,status,description,service_code,create_time,update_time)";
				$sql.="VALUES ('".$con_no."', '".$ex_oreder_id[1]."', 'Shipment', '".$status."', '".$status_code."','".$status_date."','".$update_date."')";
				$connection->query($sql);				
				$response=json_encode(array('res'=>array('status'=>array('ststus_code'=>'000','status_desc'=>'Successfully'))),JSON_UNESCAPED_SLASHES);	
				addstatus($order->getId(),'Shipment',$status,$status);
			}else{
				$response=json_encode(array("res"=>array("status"=>array("ststus_code"=>"999","status_desc"=>"Tracking Not Found"))),JSON_UNESCAPED_SLASHES);
			}		
			
		}else{			
			$response=json_encode(array("res"=>array("status"=>array("ststus_code"=>"999","status_desc"=>"Request Error"))),JSON_UNESCAPED_SLASHES);
		}

header('Content-Type: application/json');
echo $response;
?>