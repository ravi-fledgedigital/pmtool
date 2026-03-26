<?php
namespace OnitsukaTiger\CancelShipment\Model;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Class LocationReject
 * @package OnitsukaTiger\CancelShipment\Model
 */
class LocationReject {

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * LocationReject constructor.
     * @param SerializerInterface $serializer
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        SerializerInterface $serializer,
        OrderRepositoryInterface $orderRepository
    ){
        $this->serializer = $serializer;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param Order $order
     * @param $sourceCode
     */
    public function addLocationReject(Order $order, $sourceCode){
        $locationReject = array();
        if(!empty($order->getData('location_reject'))){
            $locationReject = $this->serializer->unserialize($order->getData('location_reject'));
            $locationReject [] = $sourceCode;
            $order->setLocationReject($this->serializer->serialize($locationReject));
        }else {
            $locationReject [] = $sourceCode;
            $order->setLocationReject($this->serializer->serialize($locationReject));
        }
        $this->orderRepository->save($order);
    }

}
