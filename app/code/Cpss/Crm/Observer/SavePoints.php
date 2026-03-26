<?php

namespace Cpss\Crm\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Cpss\Crm\Model\PointConfigProvider;
use Cpss\Crm\Model\CalcPointDiscount;
use Magento\Framework\UrlInterface;
/**
 * Class RecordPointsForOrderSave
 * @package Creansmaerd\Point\Observer
 */
class SavePoints implements ObserverInterface
{
    protected $pointConfigProvider;
    protected $calcPointDiscount;
    protected $url;
    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        PointConfigProvider $pointConfigProvider,
        CalcPointDiscount $calcPointDiscount,
        UrlInterface $url
    ) {
        $this->pointConfigProvider = $pointConfigProvider;
        $this->calcPointDiscount = $calcPointDiscount;
        $this->url = $url;
    }

    public function execute(Observer $observer)
    {
            $quote = "";
            $total = "";

            if (!preg_match('/checkout\/cart\/add/', $this->url->getCurrentUrl())) {
                $quote = $observer->getData('quote');
                $total = $observer->getData('total');

                $usedPoints = $quote->getUsedPoint();
                $grandTotal =  $total->getGrandTotal();
                $baseGrandTotal = $total->getBaseGrandTotal();
                $quoteDetails = [];
                $taxPercent = 0;
                foreach ($quote->getAllVisibleItems() as $item) {
                    if ($usedPoints >= 0 && $baseGrandTotal > 0) {
                        $quoteDetails[] = [
                            "sku" => $item->getSku(),
                            "qty" => (int)$item->getQty(),
                            "unitPriceExTax" => (int)$item->getPrice()
                        ];

                        $taxPercent = $item->getTaxPercent();
                    }
                }

                if ($usedPoints >= 0 && !empty($quoteDetails)) {
                    $calculatedOrderDetail = $this->calcPointDiscount->calculateOrderDetails($quoteDetails, $taxPercent, (int) $usedPoints);
                    foreach ($quote->getAllVisibleItems() as $item) {
                        if (!empty($calculatedOrderDetail)) {
                            $usedPointItem = $calculatedOrderDetail[$item->getSku()];
                            $itemDiscountAmount = $item->getDiscountAmount();
                            $itemBaseDiscountAmount = $item->getBaseDiscountAmount();

                            $item->setDiscountAmount($itemDiscountAmount + $usedPointItem);
                            $item->setBaseDiscountAmount($itemBaseDiscountAmount + $usedPointItem);
                            $item->setUsedPoint($usedPointItem);
                            $item->save();
                        }
                    }

                    $quote->setNonProportionablePoint($calculatedOrderDetail['nonProportionalbePoint']);
                    //$quote->save(); //can cause deadlock issue
                }

                if ($grandTotal != 0 && $baseGrandTotal != 0) {
                    $discountAmount = $total->getDiscountAmount();
                    $baseDiscountAmount = $total->getBaseDiscountAmount();
                    $subtotalWithDiscount = $total->getSubtotalWithDiscount();
                    $baseSubtotalWithDiscount = $total->getBaseSubtotalWithDiscount();

                    $total->setDiscountAmount($discountAmount + (int)$usedPoints);
                    $total->setBaseDiscountAmount($baseDiscountAmount + (int)$usedPoints);

                    $total->setGrandTotal($grandTotal - (int)$usedPoints);
                    $total->setBaseGrandTotal($baseGrandTotal - (int)$usedPoints);

                    $total->setSubtotalWithDiscount($subtotalWithDiscount - (int)$usedPoints);
                    $total->setBaseSubtotalWithDiscount($baseSubtotalWithDiscount - (int)$usedPoints);
                }
            }
        }
}
