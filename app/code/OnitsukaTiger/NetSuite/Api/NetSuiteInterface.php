<?php


namespace OnitsukaTiger\NetSuite\Api;


interface NetSuiteInterface
{

    /**
     * Event : update order status to shipped
     */
    const EVENT_UPDATE_ORDER_STATUS_PREPACKED = 'netsuite_update_order_status_prepacked';

    /**
     * Event : update order status to shipped
     */
    const EVENT_UPDATE_ORDER_STATUS_SHIPPED = 'netsuite_update_order_status_shipped';

    /**
     * return status : Accept
     */
    const RETURN_STATUS_ACCEPT = 'Accept';

    /**
     * return status : Accept - Process Return
     */
    const RETURN_STATUS_ACCEPT_PROCESS_RETURN = 'Accept - Process Return';

    /**
     * return status : Reject
     */
    const RETURN_STATUS_REJECT = 'Reject';

    /**
     * return status : Reject - Process Return
     */
    const RETURN_STATUS_REJECT_PROCESS_RETURN = 'Reject - Process Return';

    /**
     * API "Stock Not Available" notification from NetSuite
     * @param string $id
     * @return \OnitsukaTiger\NetSuite\Api\Response\ResponseInterface
     */
    public function orderStockNotAvailable($id);

    /**
     * API "Packed" notification from NetSuite
     * @param string $id
     * @param string $fulfillment_id
     * @return \OnitsukaTiger\NetSuite\Api\Response\ResponseInterface
     */
    public function orderPacked($id, $fulfillment_id);

    /**
     * API "Shipped" notification from NetSuite
     * @param string $id
     * @return \OnitsukaTiger\NetSuite\Api\Response\ShippedResponseInterface
     */
    public function orderShipped($id);

    /**
     * API "Cancel" from NetSuite
     * @param string $id
     * @return \OnitsukaTiger\NetSuite\Api\Response\ResponseInterface
     */
    public function orderCancel($id);

    /**
     * API "Return status update" from NetSuite
     * @param string $id
     * @param string $status
     * @return \OnitsukaTiger\NetSuite\Api\Response\ResponseInterface
     */
    public function orderReturnStatus($id, $status);

    /**
     * API Inventory sync from NetSuite
     * @return \OnitsukaTiger\NetSuite\Api\Response\InventoryResponseInterface
     */
    public function inventory();

    /**
     * API InternalId sync from NetSuite
     * @return \OnitsukaTiger\NetSuite\Api\Response\ProductInternalIdResponseInterface
     */
    public function productsInternalId();
}
