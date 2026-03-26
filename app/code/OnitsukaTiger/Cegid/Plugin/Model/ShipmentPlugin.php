<?php

namespace OnitsukaTiger\Cegid\Plugin\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order\Shipment;
use OnitsukaTiger\Cegid\Model\ShipmentUpdate;
use Magento\Weee\Helper\Data;

class ShipmentPlugin
{
    /**
     * @var Data
     */
    protected $weeeHelper;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request
     * @param Data $weeeHelper
     */
    public function __construct(
        RequestInterface $request,
        Data $weeeHelper
    ) {
        $this->request = $request;
        $this->weeeHelper = $weeeHelper;
    }

    /**
     * After Get Items
     *
     * @param Shipment $subject
     * @param mixed $shipmentItems
     * @return mixed
     */
    public function afterGetItems(Shipment $subject, mixed $shipmentItems): mixed
    {
        if (str_contains($this->request->getRequestString(), ShipmentUpdate::ROUTES_UPDATE_SHIPMENT)) {
            foreach ($shipmentItems as $k => $shipmentItem) {
                $extensionAttributes = $shipmentItem->getExtensionAttributes();
                $orderItem = $shipmentItem->getOrderItem();
                if ($orderItem === null) {
                    continue;
                }

                $totalDiscountAmount = $orderItem->getDiscountAmount() - $orderItem->getUsedPoint();

                $rate = $shipmentItem->getQty()/$orderItem->getQtyOrdered();
                $rowTotal = $this->getTotalAmount($shipmentItem->getOrderItem());
                $extensionAttributes->setTaxAmount($orderItem->getTaxAmount() * $rate);
                $extensionAttributes->setLoyaltyDiscount($orderItem->getUsedPoint() * $rate);
                $extensionAttributes->setDiscountAmount($totalDiscountAmount * $rate);
                $extensionAttributes->setRowTotal($rowTotal * $rate);
                $shipmentItem->setExtensionAttributes($extensionAttributes);
                $shipmentItems[$k] = $shipmentItem;
            }
        }

        return $shipmentItems;
    }

    /**
     * Return the total amount minus discount
     *
     * @param mixed $item
     * @return int|float
     */
    public function getTotalAmount(mixed $item): int|float
    {

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cegidShipmentPluginRowTotal.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Cegid Shipment Plugin Row Total Log Start============================');
        $logger->info('Item Row Total: ' . $item->getRowTotal());
        $logger->info('Item Discount Amount: ' . $item->getDiscountAmount());
        $logger->info('Item Tax Amount: ' . $item->getTaxAmount());
        $logger->info('Item Discount Tax Compensation Amount: ' . $item->getDiscountTaxCompensationAmount());;

        $totalAmount = ($item->getRowTotal()
            + $item->getTaxAmount()
            + $item->getDiscountTaxCompensationAmount()
            + $this->weeeHelper->getRowWeeeTaxInclTax($item)) - $item->getDiscountAmount();

        $logger->info('Total Amount: ' . $totalAmount);
        $logger->info('==========================Cegid Shipment Plugin Row Total Log Start============================');
        return $totalAmount;
    }
}
