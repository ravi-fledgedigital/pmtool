<?php

namespace OnitsukaTiger\Cegid\Plugin\Model;

use OnitsukaTiger\Shipment\Model\CoreEventHandle;
use OnitsukaTiger\Cegid\Model\ShipmentUpdate;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderRepositoryInterface;

class CoreEventHandlePlugin
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param RequestInterface $request
     * @param ResourceConnection $resourceConnection
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        RequestInterface $request,
        ResourceConnection $resourceConnection,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->request            = $request;
        $this->resourceConnection = $resourceConnection;
        $this->orderRepository = $orderRepository;
    }

    public function aroundSaveShipmentNumber(CoreEventHandle $subject, callable $proceed, $shipment)
    {
        if (strpos($this->request->getRequestString(), ShipmentUpdate::ROUTES_UPDATE_SHIPMENT) !== false) {
            $order = $this->orderRepository->get($shipment->getOrderId());
            $shipmentNumberIncrement = $order->getData('order_shipment_number_increment') + 1;
            $order->setData('order_shipment_number_increment', $shipmentNumberIncrement);
            $this->orderRepository->save($order);

            $connection = $this->resourceConnection->getConnection();
            $table      = $connection->getTableName('sales_shipment');
            $where = "`entity_id` = '".$shipment->getEntityId()."'";
            $connection->update(
                $table,
                ['shipment_number' => $shipmentNumberIncrement],
                $where
            );

            return true;
        }

        return $proceed($shipment);
    }
}
