<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\PreOrders\Plugin\InventoryShippingAdminUi\Controller\Adminhtml\SourceSelection;

class DisallowShipmentPreorderPlugin
{
    const PRODUCT_TYPE = 'simple';

    /**
     * @var \OnitsukaTiger\PreOrders\Helper\PreOrder
     */
    protected $preOrderHelper;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * @param PreOrder $preOrderHelper
     */
    public function __construct(
        \OnitsukaTiger\PreOrders\Helper\PreOrder $preOrderHelper,
        \Magento\Sales\Model\Order $order,
        private \Magento\Framework\Message\ManagerInterface $messageManager,
        private \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirectFactory
    ) {
        $this->preOrderHelper = $preOrderHelper;
        $this->order = $order;
    }

    /**
     * @param \Magento\InventoryShippingAdminUi\Controller\Adminhtml\SourceSelection\Index $subject
     * @param \Closure $proceed 
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function beforeExecute(
        \Magento\InventoryShippingAdminUi\Controller\Adminhtml\SourceSelection\Index $subject
    )
    {
        $request = $subject->getRequest();
        $resultRedirect = $this->resultRedirectFactory->create();
        
        $isProductPreOrderStatus = false;
        if($request->getParams()['order_id']){
            $orderId = $request->getParams()['order_id'];
            $order = $this->order->load($orderId);
            $orderItems = $order->getAllItems();

            foreach ($orderItems as $item) {
                $product_id = $item->getProductId();
                if ($item->getProductType() == self::PRODUCT_TYPE) {
                    $isProductPreOrder = $this->preOrderHelper->checkPreOrderForShipment($product_id, $order->getStoreId());
                    if($isProductPreOrder) {
                        $isProductPreOrderStatus = $isProductPreOrder;
                        break;
                    }
                }
            }
        }
        if($isProductPreOrderStatus){
            $this->messageManager->addErrorMessage(__('Cannot generate the shipment at the moment, due to the current order having active pre-order products.'));
            return $resultRedirect->setPath('sales/order/view/', ['order_id' => $order->getId()]);

        }
    }
}