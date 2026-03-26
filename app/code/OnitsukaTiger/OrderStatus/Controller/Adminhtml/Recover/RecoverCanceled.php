<?php

namespace OnitsukaTiger\OrderStatus\Controller\Adminhtml\Recover;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\InventorySalesAsyncOrder\Model\Reservations;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use Magento\InventorySales\Model\ReservationExecutionInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySales\Model\AppendReservations;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;

class RecoverCanceled extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::recover_canceled';

    /**
     * @var OrderRepository
     */
    protected OrderRepository $orderRepository;

    /**
     * @var OrderStatus
     */
    protected OrderStatus $orderStatusModel;

    /**
     * @var ReservationExecutionInterface
     */
    private $reservationExecution;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var AppendReservations
     */
    private $appendReservations;

    /**
     * @var ItemToSellInterfaceFactory
     */
    private $itemsToSellFactory;

    /**
     * @param Context $context
     * @param OrderRepository $orderRepository
     * @param OrderStatus $orderStatusModel
     * @param ReservationExecutionInterface $reservationExecution
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param AppendReservations $appendReservations
     */
    public function __construct(
        Context         $context,
        OrderRepository $orderRepository,
        OrderStatus $orderStatusModel,
        ReservationExecutionInterface $reservationExecution,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        AppendReservations $appendReservations,
        ItemToSellInterfaceFactory $itemsToSellFactory
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->orderStatusModel = $orderStatusModel;
        $this->reservationExecution = $reservationExecution;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->appendReservations = $appendReservations;
        $this->itemsToSellFactory = $itemsToSellFactory;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = $this->getRequest()->getParam('ord_id');

        if ($orderId) {
            $orderViewUrl = $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
            try {
                /** @var Order $order */
                $order = $this->orderRepository->get($orderId);
                $this->orderStatusModel->recoverStatusCanceled($order);
                $this->reservationValue($order);
                $this->messageManager->addSuccessMessage(__('Recover Order Canceled Successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            return $orderViewUrl;
        }

        return $resultRedirect->setPath('*/*/');
    }

    public function reservationValue($order)
    {
        if ($this->reservationExecution->isDeferred()) {
            $itemsById = $itemsBySku = $itemsToSell = [];
            foreach ($order->getItems() as $item) {
                if (!isset($itemsById[$item->getProductId()])) {
                    $itemsById[$item->getProductId()] = 0;
                }
                $itemsById[$item->getProductId()] += $item->getQtyOrdered();
            }
            $productSkus = $this->getSkusByProductIds->execute(array_keys($itemsById));
            $productTypes = $this->getProductTypesBySkus->execute($productSkus);

            foreach ($productSkus as $productId => $sku) {
                if (false === $this->isSourceItemManagementAllowedForProductType->execute($productTypes[$sku])) {
                    continue;
                }

                $itemsBySku[$sku] = (float)$itemsById[$productId];
                $itemsToSell[] = $this->itemsToSellFactory->create([
                    'sku' => $sku,
                    'qty' => -(float)$itemsById[$productId]
                ]);
            }

            $websiteId = (int)$order->getStore()->getWebsiteId();
            $this->appendReservations->reserve($websiteId, $itemsBySku, $order, $itemsToSell);
        }
    }
}