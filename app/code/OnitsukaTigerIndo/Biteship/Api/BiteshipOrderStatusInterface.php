<?php


namespace OnitsukaTigerIndo\Biteship\Api;

interface BiteshipOrderStatusInterface
{
    /**
     * Event : update order status to shipped
     */
    public const EVENT_UPDATE_ORDER_STATUS_SHIPPED = 'biteship_update_order_status_shipped';

    /**
     * Event : update order status to delivered
     */
    public const EVENT_UPDATE_ORDER_STATUS_DELIVERED = 'biteship_update_order_status_delivered';

    /**
     * API for prepacked status update from Biteship
     *
     * @param string $id
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     */
    public function statusPrepacked($id);

    /**
     * API for packed status update from Biteship
     *
     * @param string $id
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     */
    public function statusPacked($id);

    /**
     * API for shipped status update from Biteship
     *
     * @param string $id
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     */
    public function statusShipped($id);

    /**
     * API for Delivered status update from Biteship
     *
     * @param string $id
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     */
    public function statusDelivered($id);

    /**
     * API for Return status update from Biteship
     *
     * @param string $id
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     */
    public function statusCancel($id);

    /**
     * API for update order and shipment status from Biteship
     *
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     */
    public function statusUpdate();
}
