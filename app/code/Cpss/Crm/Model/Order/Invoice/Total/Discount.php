<?php

namespace Cpss\Crm\Model\Order\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;

/**
 * Discount invoice
 */
class Discount extends \Magento\Sales\Model\Order\Invoice\Total\Discount
{
    /**
     * Collect invoice
     *
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $invoice->setDiscountAmount(0);
        $invoice->setBaseDiscountAmount(0);

        $order = $invoice->getOrder();

        $totalDiscountAmount = 0;
        $baseTotalDiscountAmount = 0;
        $isLast = [];

        /**
         * Checking if shipping discount was added in previous invoices.
         * So basically if we have invoice with positive discount and it
         * was not canceled we don't add shipping discount to this one.
         */
        if ($this->isShippingDiscount($invoice)) {
            $totalDiscountAmount = $totalDiscountAmount + $invoice->getOrder()->getShippingDiscountAmount();
            $baseTotalDiscountAmount = $baseTotalDiscountAmount +
                $invoice->getOrder()->getBaseShippingDiscountAmount();
        }

        /** @var $item \Magento\Sales\Model\Order\Invoice\Item */
        foreach ($invoice->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if ($orderItem->isDummy()) {
                continue;
            }

            $isLast[] = $item->isLast();
            $orderItemDiscount = (double)$orderItem->getDiscountAmount();
            $baseOrderItemDiscount = (double)$orderItem->getBaseDiscountAmount();
            $orderItemQty = $orderItem->getQtyOrdered();

            if ($orderItemDiscount && $orderItemQty) {
                /**
                 * Resolve rounding problems
                 */
                $discount = $orderItemDiscount - $orderItem->getDiscountInvoiced();
                $baseDiscount = $baseOrderItemDiscount - $orderItem->getBaseDiscountInvoiced();
                if (!$item->isLast()) {
                    $activeQty = $orderItemQty - $orderItem->getQtyInvoiced();
                    $discount = $invoice->roundPrice($discount / $activeQty * $item->getQty(), 'regular', true);
                    $baseDiscount = $invoice->roundPrice($baseDiscount / $activeQty * $item->getQty(), 'base', true);
                }

                $item->setDiscountAmount($discount);
                $item->setBaseDiscountAmount($baseDiscount);

                $totalDiscountAmount += $discount;
                $baseTotalDiscountAmount += $baseDiscount;
            }
        }

        if (!in_array(false, $isLast)) {
            $totalDiscountAmount = $totalDiscountAmount + $order->getNonProportionablePoint();
            $baseTotalDiscountAmount = $baseTotalDiscountAmount + $order->getNonProportionablePoint();
        }

        $invoice->setDiscountAmount(-$totalDiscountAmount);
        $invoice->setBaseDiscountAmount(-$baseTotalDiscountAmount);

        $grandTotal = $invoice->getGrandTotal() - $totalDiscountAmount;
        $baseGrandTotal = $invoice->getBaseGrandTotal() - $baseTotalDiscountAmount;
        
        $invoice->setGrandTotal($grandTotal);
        $invoice->setBaseGrandTotal($baseGrandTotal);

        return $this;
    }

    /**
     * Checking if shipping discount was added in previous invoices.
     *
     * @param Invoice $invoice
     * @return bool
     */
    private function isShippingDiscount(Invoice $invoice): bool
    {
        $addShippingDiscount = true;
        foreach ($invoice->getOrder()->getInvoiceCollection() as $previousInvoice) {
            if ($previousInvoice->getDiscountAmount()) {
                $addShippingDiscount = false;
            }
        }
        return $addShippingDiscount;
    }
}
