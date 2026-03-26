<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTigerKorea\Customer\Controller\Guest;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Helper\Guest as GuestHelper;
use Magento\Sales\Model\OrderFactory;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTiger\Sales\Helper\Data;

/**
 * Class Cancel
 * @package OnitsukaTiger\Sales\Controller\Guest
 */
class Cancel extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface, HttpGetActionInterface
{



    /**
     * @var PageFactory
     */
    private $resultPageFactory;


    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var GuestHelper
     */
    private $guestHelper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \OnitsukaTigerKorea\Sales\Model\Order\Cancel
     */
    protected $order;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var \OnitsukaTiger\Store\Helper\Data
     */
    private $helperStore;

    /**
     * @var \OnitsukaTiger\OrderStatusTracking\Helper\Data
     */
    private $helperTracking;

    /**
     * Cancel constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param PageFactory $resultPageFactory
     * @param GuestHelper $guestHelper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param OrderManagementInterface $orderManagement
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \OnitsukaTigerKorea\Sales\Model\Order\Cancel $order
     * @param Logger $logger
     * @param \OnitsukaTiger\Store\Helper\Data $data
     * @param \OnitsukaTiger\OrderStatusTracking\Helper\Data $helperTracking
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        PageFactory $resultPageFactory,
        GuestHelper $guestHelper,
        \Magento\Framework\Registry $coreRegistry,
        OrderManagementInterface $orderManagement,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \OnitsukaTigerKorea\Sales\Model\Order\Cancel $order,
        Logger $logger,
        \OnitsukaTiger\Store\Helper\Data $data,
        \OnitsukaTiger\OrderStatusTracking\Helper\Data $helperTracking,
        OrderFactory $orderFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->coreRegistry = $coreRegistry;
        $this->messageManager = $messageManager;
        $this->resultPageFactory = $resultPageFactory;
        $this->orderManagement = $orderManagement;
        $this->guestHelper = $guestHelper;
        $this->order = $order;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->helperStore = $data;
        $this->helperTracking = $helperTracking;
        parent::__construct($context);
    }
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result =  $this->guestHelper->loadValidOrder($this->getRequest());
        if ($result instanceof ResultInterface) {
            return $result;
        }
        $order = $this->coreRegistry->registry('current_order');
        $this->coreRegistry->unregister('current_order');
        $response = [
            'success' => 1
        ];
        if ($order && $order->hasInvoices()) {
            try {
                if ($order->hasShipments()) {
                    $this->order->deleteShipmentWhenCancelOrder($order);
                }
                $this->order->createCreditMemoWhenCustomerCancelOrder($order);
                $this->order->addOrderCancelReason($order, $this->getRequest()->getParam('reason'));

                $this->messageManager->addSuccessMessage(__('You canceled the order.'));
                $this->logger->info(sprintf('You canceled the order [%s].', $order->getIncrementId()));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->error($e->getMessage());
                $response['success'] = 0;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
                $this->logger->error($e->getMessage());
                $response['success'] = 0;
            }
        }else {
            $this->messageManager->addErrorMessage(__('You have not canceled the item at current time. Please try another time!'));
            $this->logger->error(sprintf('Order [%s] do not have any invoice. Do not cancel order at current time', $order->getIncrementId()));
            $response['success'] = 0;
        }
        $order = $this->orderFactory->create()->load($this->getRequest()->getParam('oar_order_id'), "increment_id");

        if ($order->getUpdatedAt()) {
            $updateDate = $this->helperStore->formatDate($order->getUpdatedAt(),$order->getStoreId());
        }
        $statusOrderTrack = $this->helperTracking->getStatusTracking($order);
        $index=0;
        $totalTrack = count($statusOrderTrack);
        foreach ($statusOrderTrack as $key => $track) {
            $index++;
            if((int)$index == $totalTrack){
                $updateDate = $this->helperStore->formatDate($track->getCreatedAt(),$order->getStoreId());
            }
        }
        $orderStatus = $order->getStatusLabel();
        $response['statusOrder'] = '<span>'. __($orderStatus).'</span>' . __('on '). $updateDate;
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($response);
        return $resultJson;
    }
}
