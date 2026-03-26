<?php
namespace Clickend\Kerry\Model;
use Exception;
use Clickend\Kerry\Api\ShipingStatusInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\CouldNotSaveException;
class shipingstatusdata implements ShipingStatusInterface
{
	protected $dataHelper;
	private $request;
	protected $resourceConnection;
	private $order;
	protected $_orderRepository;
	protected $_shipmentTrackFactory;
	protected $_shipmentFactory;
	protected $_transactionFactory;
	
	public function __construct(
        Http $request,
		\Clickend\Kerry\Helper\Data $dataHelper,
		\Magento\Framework\App\ResourceConnection $resourceConnection,
		\Magento\Sales\Api\Data\OrderInterface $order,
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackFactory,
		\Magento\Framework\DB\TransactionFactory $transactionFactory,
    	\Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory
		
		
    ) {
		$this->dataHelper = $dataHelper; 
        $this->request = $request;
		$this->resourceConnection = $resourceConnection;
		$this->order = $order;
		$this->_orderRepository = $orderRepository;
		$this->_shipmentTrackFactory = $shipmentTrackFactory;
      	$this->_shipmentFactory = $shipmentFactory;
		$this->_transactionFactory = $transactionFactory;
    }
	
    public function shipingstatusdata()
    {		
		$helper = $this->dataHelper;
		
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ShippingStatus.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		
		$logger->info("================ Shipping Update Staus =================");
		$data=json_decode($this->request->getContent(),true);
		$logger->info(print_r($data,true));
				
		
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
			
			
			$check_con = $this->resourceConnection->getConnection()->fetchAll("select count(con_no) as num from kerry_shipping_track where con_no='".$con_no."';");
			$logger->info("================ Query Found [".$check_con[0]['num']."] =================");
			
			
			if($check_con[0]['num']>=1){				
				
				$order = $this->order->loadByIncrementId($ex_oreder_id[1]);	
				
				if($status_code=="POD"){
					$newState = \Magento\Sales\Model\Order::STATE_COMPLETE;
					$order->setState($newState)->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
					$order->save();
				}
				
				
				$sql="INSERT INTO kerry_shipping_track_history (con_no,order_id,status,description,service_code,create_time,update_time)";
				$sql.="VALUES ('".$con_no."', '".$ex_oreder_id[1]."', 'Shipment', '".$status."', '".$status_code."','".$status_date."','".$update_date."')";
				$connection = $this->resourceConnection->getConnection()->query($sql);	
				$response['res']=array("res"=>array("status"=>array("ststus_code"=>"000","status_desc"=>"Successfully")));
				
				$logger->info("Query Insert shipping status Successfully");
				//$this->createShipment($order->getId(),$status_desc,$location2);
				$this->addstatus($order->getId(),'Shipment',$status,$status);
			}else{
				$response['res']=array("res"=>array("status"=>array("ststus_code"=>"999","status_desc"=>"Unsuccessfully")));
				$logger->info("Query Insert shipping status Unsuccessfully");
			}		
			
		}else{
			$response['res']=array("res"=>array("status"=>array("ststus_code"=>"999","status_desc"=>"Unsuccessfully")));
			$logger->info("Query Insert shipping status Unsuccessfully");
		}	
		
		
	   return  json_decode(json_encode($response),true);
    }
	

public function addstatus($order_id,$title,$tracking,$desc) {
	
	$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ShippingStatus.log');
	$logger = new \Zend\Log\Logger();
	$logger->addWriter($writer);
	
    // Load up the order
     $order = $this->_orderRepository->get($order_id);

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
		$connection = $this->resourceConnection->getConnection()->query($sqladdStatus);		

    } else {
		$logger->info("Shipment Not Created Because It's already created or something went wrong ");       
   }
}	
	
	
	
	
	
	
	
/*	
	
	
	
	
protected function createShipment($orderId,$title,$location)
{
	
	$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ShippingStatus.log');
	$logger = new \Zend\Log\Logger();
	$logger->addWriter($writer);
	
    try {
        $order = $this->_orderRepository->get($orderId);
		$shipment_detail= $order->getShipmentsCollection();
		$shipmentId = 0;
		foreach ($shipment_detail as $shipment) {
			$shipmentId = $shipment->getId();
		}
		
		
        if ($order){
            $data = array(array(
                'carrier_code' => $order->getShippingMethod(),
                'title' => $title,
                'number' => $location,
            ));
            $shipment = $this->prepareShipment($order, $data);
			
			
		$logger->info("Shippiment ".$shipmentId."  ".print_r($shipment,true));	
			
            if ($shipment) {
              //  $order->setIsInProcess(true);
              //  $order->addStatusHistoryComment('Shipping [ '.$title.' '.$location.' ]', false);
                $transactionSave =  $this->_transactionFactory->create()->addObject($shipment)->addObject($shipment->getOrder());
                $transactionSave->save();
				
				$logger->info("Trackking Added ".print_r($data,true));
				
            }
            return $shipment;
        }
    } catch (\Exception $e) {  
		$logger->info("Trackking Error ".$e->getMessage());
		
    }
}

protected function prepareShipment($order, $track)
{
   $shipment = $this->_shipmentFactory->create(
       $order,
       $this->prepareShipmentItems($order),
       $track
   );
   return $shipment->getTotalQty() ? $shipment->register() : false;
}
 
protected function prepareShipmentItems($order)
{
   $items = [];
 
   foreach($order->getAllItems() as $item) {
       $items[$item->getItemId()] = $item->getQtyOrdered();
   }
   return $items;
}
*/	
	
}