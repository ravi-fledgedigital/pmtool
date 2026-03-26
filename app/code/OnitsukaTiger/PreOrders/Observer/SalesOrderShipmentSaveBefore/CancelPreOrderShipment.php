<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\PreOrders\Observer\SalesOrderShipmentSaveBefore;

use Magento\Framework\Event\ObserverInterface;
use OnitsukaTiger\PreOrders\Helper\PreOrder;

class CancelPreOrderShipment implements ObserverInterface
{
	const PRODUCT_TYPE = 'simple';

    /**
     * @var PreOrder
     */
    protected $preOrderHelper;

    /**
     * @param PreOrder $preOrderHelper
     */
    public function __construct(
        PreOrder $preOrderHelper
    ) {
        $this->preOrderHelper = $preOrderHelper;
    }

    /**
     * cancel shipmetn if pre order is enabled of the ordered product
     *
     * @param  \Magento\Framework\Event\Observer $observer
     * @return string
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getShipment()->getOrder();
        $items = $order->getAllItems();

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/preorder_stop_shipment.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info('store id '. $order->getStoreId());

	    if($items) {
	        foreach ($items as $_item) {
	            $product_id = $_item->getProductId();
        		$logger->info('order id  '. $order->getIncrementId());
                $isProductPreOrder = false;
        		if ($_item->getProductType() == self::PRODUCT_TYPE) {
        			$logger->info('product id - '. $product_id);
        			$logger->info('product type -  '. $_item->getProductType());
		            $isProductPreOrder = $this->preOrderHelper->checkPreOrderForShipment($product_id, $order->getStoreId());
	                $logger->info('is Pre Order product : '.$isProductPreOrder);

		            if($isProductPreOrder) {
		            	$logger->info('Cancelling Shipment for Pre Order : '. $order->getIncrementId().', product SKU : '.$_item->getSku());
		                $message = 'Cannot generate the shipment at the moment, due to the current order having active pre-order products.';
		                throw new \Magento\Framework\Exception\LocalizedException(__($message));
		            }
		        }
	        }
	    }

        return $this;
    }
}
