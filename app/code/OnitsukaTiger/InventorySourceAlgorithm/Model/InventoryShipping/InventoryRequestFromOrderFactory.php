<?php
declare(strict_types=1);

namespace OnitsukaTiger\InventorySourceAlgorithm\Model\InventoryShipping;

use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestExtensionInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Item as OrderItem;

class InventoryRequestFromOrderFactory extends \Magento\InventoryShipping\Model\InventoryRequestFromOrderFactory {

    /**
     * @var ItemRequestInterfaceFactory
     */
    private $itemRequestFactory;

    /**
     * @var InventoryRequestInterfaceFactory
     */
    private $inventoryRequestFactory;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var GetSkuFromOrderItemInterface
     */
    private $getSkuFromOrderItem;

    /**
     * @var InventoryRequestExtensionInterfaceFactory
     */
    private $inventoryRequestExtensionFactory;

    /**
     * InventoryRequestFromOrderFactory constructor.
     * @param ItemRequestInterfaceFactory $itemRequestFactory
     * @param InventoryRequestInterfaceFactory $inventoryRequestFactory
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param GetSkuFromOrderItemInterface $getSkuFromOrderItem
     * @param InventoryRequestExtensionInterfaceFactory $inventoryRequestExtensionFactory
     */
    public function __construct(
        ItemRequestInterfaceFactory $itemRequestFactory,
        InventoryRequestInterfaceFactory $inventoryRequestFactory,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        InventoryRequestExtensionInterfaceFactory $inventoryRequestExtensionFactory
    )
    {
        parent::__construct(
            $itemRequestFactory,
            $inventoryRequestFactory,
            $stockByWebsiteIdResolver,
            $getSkuFromOrderItem
        );

        $this->itemRequestFactory = $itemRequestFactory;
        $this->inventoryRequestFactory = $inventoryRequestFactory;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->inventoryRequestExtensionFactory = $inventoryRequestExtensionFactory;
    }

    /**
     * @param OrderInterface $order
     * @return InventoryRequestInterface
     */
    public function create(OrderInterface $order): InventoryRequestInterface
    {
        $requestItems = [];
        $websiteId = $order->getStore()->getWebsiteId();
        $stockId = (int)$this->stockByWebsiteIdResolver->execute((int)$websiteId)->getStockId();

        /** @var OrderItemInterface|OrderItem $orderItem */
        foreach ($order->getItems() as $orderItem) {
            $itemSku = $this->getSkuFromOrderItem->execute($orderItem);
            $qtyToDeliver = $orderItem->getQtyToShip();

            //check if order item is not delivered yet
            if ($orderItem->isDeleted()
                || $orderItem->getParentItemId()
                || $this->isZero((float)$qtyToDeliver)
                || $orderItem->getIsVirtual()
            ) {
                continue;
            }

            $requestItems[] = $this->itemRequestFactory->create([
                'sku' => $itemSku,
                'qty' => $qtyToDeliver
            ]);
        }

        $inventoryRequest =  $this->inventoryRequestFactory->create([
            'stockId' => $stockId,
            'items' => $requestItems
        ]);

        $extensionAttributes = $this->inventoryRequestExtensionFactory->create();
        $extensionAttributes->setOrder($order);
        $inventoryRequest->setExtensionAttributes($extensionAttributes);

        return $inventoryRequest;
    }

    /**
     * Compare float number with some epsilon
     *
     * @param float $floatNumber
     *
     * @return bool
     */
    private function isZero(float $floatNumber): bool
    {
        return $floatNumber < 0.0000001;
    }
}
