<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\ShipmentCommentSender;
use Magento\Backend\App\Action;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use OnitsukaTiger\Logger\StoreShipping\Logger;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;

abstract class AbstractAddComment extends \Magento\Backend\App\Action
{

    /**
     * @var ShipmentLoader
     */
    protected $shipmentLoader;

    /**
     * @var ShipmentCommentSender
     */
    protected $shipmentCommentSender;

    /**
     * @var LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @var StoreShipping
     */
    protected $storeShipping;

    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Action\Context $context
     * @param ShipmentLoader $shipmentLoader
     * @param ShipmentCommentSender $shipmentCommentSender
     * @param LayoutFactory $resultLayoutFactory
     * @param StoreShipping $storeShipping
     * @param ShipmentRepository $shipmentRepository
     * @param Logger $logger
     */
    public function __construct(
        Action\Context $context,
        ShipmentLoader $shipmentLoader,
        ShipmentCommentSender $shipmentCommentSender,
        LayoutFactory $resultLayoutFactory,
        StoreShipping $storeShipping,
        ShipmentRepository $shipmentRepository,
        Logger $logger
    ) {
        $this->shipmentLoader = $shipmentLoader;
        $this->shipmentCommentSender = $shipmentCommentSender;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->storeShipping = $storeShipping;
        $this->shipmentRepository = $shipmentRepository;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Add comment to shipment history
     *
     * @return void
     */
    public function execute($checkIsShippingFromStore = true)
    {
        try {
            $this->getRequest()->setParam('shipment_id', $this->getRequest()->getParam('id'));
            $data = $this->getRequest()->getPost('comment');
            if (empty($data['comment'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The comment is missing. Enter and try again.')
                );
            }
            $this->shipmentLoader->setOrderId($this->getRequest()->getParam('order_id'));
            $this->shipmentLoader->setShipmentId($this->getRequest()->getParam('shipment_id'));
            $this->shipmentLoader->setShipment($this->getRequest()->getParam('shipment'));
            $this->shipmentLoader->setTracking($this->getRequest()->getParam('tracking'));
            $shipment = $this->shipmentLoader->load();
            $shipment->addComment(
                $data['comment'],
                isset($data['is_customer_notified']),
                isset($data['is_visible_on_front'])
            );

            // Add shipment comment to order history
            if ($checkIsShippingFromStore) {
                $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
                if (!$this->storeShipping->isShippingFromWareHouse($sourceCode)) {
                    $this->addCommentToOrder($shipment, $data['comment']);
                }
            } else {
                $this->addCommentToOrder($shipment, $data['comment']);
            }

            $this->shipmentCommentSender->send($shipment, !empty($data['is_customer_notified']), $data['comment']);
            $shipment->save();
            $resultLayout = $this->resultLayoutFactory->create();
            $resultLayout->addDefaultHandle();
            $response = $resultLayout->getLayout()->getBlock('shipment_comments')->toHtml();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
            $this->logger->error(sprintf('SPS: Error add comment shipment [%s]. Message: [%s]', $this->getRequest()->getParam('id'), $e->getMessage()));
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('Cannot add new comment.')];
            $this->logger->error(sprintf('Cannot add new comment [%s]. Message: [%s]', $this->getRequest()->getParam('id'), $e->getMessage()));
        }
        if (is_array($response)) {
            $response = $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($response);
            $this->getResponse()->representJson($response);
        } else {
            $this->getResponse()->setBody($response);
        }
    }

    /**
     * @param $shipment
     * @param $comment
     * @throws \Exception
     */
    private function addCommentToOrder($shipment, $comment)
    {
        /** @var Order $order */
        $order = $shipment->getOrder();
        $order->addCommentToStatusHistory(
            __('Shipment #%1 comment added: "%2"', $shipment->getIncrementId(), $comment)
        );
        $order->save();
    }
}
