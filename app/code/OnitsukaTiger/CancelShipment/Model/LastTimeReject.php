<?php
namespace OnitsukaTiger\CancelShipment\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class LastTimeReject
 * @package OnitsukaTiger\CancelShipment\Model
 */
class LastTimeReject {

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * LastTimeReject constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param DateTime $date
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        DateTime $date
    ){
        $this->orderRepository = $orderRepository;
        $this->date = $date;
    }

    /**
     * @param Order $order
     */
    public function addLastTimeReject(Order $order)
    {
        $currentTime = $this->date->gmtDate();
        $order->setLastTimeReject($currentTime);
        $this->orderRepository->save($order);
    }
}
