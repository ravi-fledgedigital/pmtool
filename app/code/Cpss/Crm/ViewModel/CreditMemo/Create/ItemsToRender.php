<?php

declare(strict_types=1);

namespace Cpss\Crm\ViewModel\CreditMemo\Create;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items;
use Magento\Sales\Model\Convert\OrderFactory;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order\Creditmemo\Item;

/**
 * View model to return creditmemo items for rendering
 */
class ItemsToRender extends \Magento\Sales\ViewModel\CreditMemo\Create\ItemsToRender
{
    /**
     * @var Items
     */
    private $items;

    /**
     * @var ConvertOrder
     */
    private $converter;

    /**
     * @param Items $items
     * @param OrderFactory $convertOrderFactory
     */
    public function __construct(
        Items $items,
        OrderFactory $convertOrderFactory
    ) {
        $this->items = $items;
        $this->converter = $convertOrderFactory->create();
        parent::__construct($items, $convertOrderFactory);
    }

    /**
     * Return creditmemo items for rendering and make sure all its parents are included
     *
     * @return Item[]
     */
    public function getItems(): array
    {
        $creditMemo = null;
        $parents = [];
        $items = [];

        foreach ($this->items->getCreditmemo()->getAllItems() as $item) {
            if (!$creditMemo) {
                $creditMemo = $item->getCreditmemo();
            }
            $orderItem = $item->getOrderItem();
            if ($orderItem->getChildrenItems()) {
                $parents[] = $orderItem->getItemId();
            }
        }

        $subTotal = 0;
        $taxAmount = 0;
        $discountAmount = 0;
        $discountedAmount = 0;
        if ($creditMemo) {
            $subTotal = $creditMemo->getSubtotal();
            $taxAmount = $creditMemo->getTaxAmount();
            $discountAmount = $creditMemo->getDiscountAmount();

            $subTotalIncTax = round($subTotal) + round(abs($taxAmount));
            $discountAmount = round(abs($discountAmount));
            // If negative result means adjust total discount
            $discountedAmount = $subTotalIncTax - $discountAmount; 
        }

        if ($discountedAmount < 0) {
            $creditMemo->setGrandTotal($creditMemo->getGrandTotal() - $discountedAmount);
            $creditMemo->setBaseGrandTotal($creditMemo->getBaseGrandTotal() - $discountedAmount);
        } 

        foreach ($this->items->getCreditmemo()->getAllItems() as $item) {
            $orderItemParent = $item->getOrderItem()->getParentItem();
            if ($orderItemParent && !in_array($orderItemParent->getItemId(), $parents)) {
                $itemParent = $this->converter->itemToCreditmemoItem($orderItemParent);
                $itemParent->setCreditmemo($creditMemo)
                    ->setParentId($creditMemo->getId())
                    ->setStoreId($creditMemo->getStoreId());
                $items[] = $itemParent;
                $parents[] = $orderItemParent->getItemId();
            }
            $items[] = $item;
        }
        return $items;
    }
}
