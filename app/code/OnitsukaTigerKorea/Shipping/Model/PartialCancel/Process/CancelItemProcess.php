<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\Shipping\Model\PartialCancel\Process;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;

class CancelItemProcess {

    /**
     * @param OrderInterface $order
     * @param array $data
     * @return mixed
     */
    public function execute(OrderInterface $order, array $data){
        // check order item, set qty_cancel for this item = qty input
        $itemCancelQty = $data['cancel_items'];
        $totalAmountCanceled = 0; $taxAmountCanceled = 0; $shippingAmountCanceled = 0; $discountAmountCanceled = 0;
        $itemInfo = []; $itemShip = [];
        foreach ($order->getAllItems() as $item) {
            foreach($itemCancelQty as $itemId => $qtyCancel) {
                if($item->getItemId() == $itemId) {
                    $item = $this->cancelItem($item,$qtyCancel);
                    $itemInfo [] = '<br> Item SKU: ' . $item->getSku() . ', Quantity: ' . $qtyCancel;
                    $itemShip [] = '<br> Item SKU: ' . $item->getSku() . ', Quantity: ' . $data['items'][$itemId];
                    $totalAmountCanceled += $item->getRowTotal()*$qtyCancel/$item->getQtyOrdered();
                    $taxAmountCanceled += $item->getTaxAmount()*$qtyCancel/$item->getQtyOrdered();
                    $discountAmountCanceled += $item->getDiscountAmount()*$qtyCancel/$item->getQtyOrdered();

                }
            }
        }
        $order = $this->registerCancellation($order,$totalAmountCanceled, $taxAmountCanceled, $discountAmountCanceled);

        $comment = sprintf('<b>Partial Cancel process: Canceled item: </b> %s. <br> <b> Shipment: %s was created with item: </b> %s',
            implode('', $itemInfo), $order->getShipmentsCollection()->getFirstItem()->getIncrementId(), implode('', $itemShip)
        );
        $order->addCommentToStatusHistory($comment, $order->getStatus());

        return $order;
    }

    /**
     * @param Item $item
     * @param $qty
     * @return Item
     */
    private function cancelItem(\Magento\Sales\Model\Order\Item $item, $qty)
    {
        $item->setQtyPartiallyCanceled($item->getQtyPartiallyCanceled() + $qty);
        $item->setTaxCanceled(
            $item->getTaxCanceled() + $item->getBaseTaxAmount() * $qty / $item->getQtyOrdered()
        );
        $item->setDiscountTaxCompensationCanceled(
            $item->getDiscountTaxCompensationCanceled() +
            $item->getDiscountTaxCompensationAmount() * $qty / $item->getQtyOrdered()
        );
        return $item;
    }

    private function registerCancellation(\Magento\Sales\Api\Data\OrderInterface $order, $totalAmountCanceled, $taxAmountCanceled, $discountAmountCanceled)
    {
        $totalAmountCancel = $order->getBaseTotalCanceled()+$totalAmountCanceled;
        $taxAmountCancel = $order->getTaxCanceled() + $taxAmountCanceled;
        $discountCancel = $order->getDiscountCanceled() + $discountAmountCanceled;
        $order->setBaseTotalCanceled($totalAmountCancel)->setTotalCanceled($totalAmountCancel)
            ->setBaseSubtotalCanceled($totalAmountCancel)->setSubtotalCanceled($totalAmountCancel);
        $order->setTaxCanceled($taxAmountCancel);
        $order->setBaseTaxCanceled($taxAmountCancel);
        $order->setDiscountCanceled($discountCancel);
        $order->setBaseDiscountCanceled($discountCancel);

        return $order;
    }
}
