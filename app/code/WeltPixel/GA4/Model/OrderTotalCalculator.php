<?php

namespace WeltPixel\GA4\Model;

/**
 * Class \WeltPixel\GA4\Model\OrderTotalCalculator
 */
class OrderTotalCalculator
{
    /**
     * @param $order
     * @param $ga4Helper
     * @return float
     */
    public function calculateOrderTotal($order, $ga4Helper)
    {
        $addonOrderTotalCalculation = \WeltPixel\GA4\Model\Config\Source\AddonOrderTotalCalculation::CALCULATE_DEFAULT;
        if (method_exists($ga4Helper, 'getAddonOrderTotalCalculation')) {
            $addonOrderTotalCalculation = $ga4Helper->getAddonOrderTotalCalculation();
        }

        if ($addonOrderTotalCalculation != \WeltPixel\GA4\Model\Config\Source\AddonOrderTotalCalculation::CALCULATE_DEFAULT) {
            return $this->calculateAddonOrderTotal($order, $ga4Helper);
        }


        $orderTotalCalculationOption = $ga4Helper->getOrderTotalCalculation();
        switch ($orderTotalCalculationOption) {
            case \WeltPixel\GA4\Model\Config\Source\OrderTotalCalculation::CALCULATE_SUBTOTAL:
                $orderTotal = $order->getSubtotal();
                break;
            case \WeltPixel\GA4\Model\Config\Source\OrderTotalCalculation::CALCULATE_GRANDTOTAL:
            default:
                $orderTotal = $order->getGrandtotal();
                if ($ga4Helper->excludeTaxFromTransaction()) {
                    $orderTotal -= $order->getTaxAmount();
                }

                if ($ga4Helper->excludeShippingFromTransaction()) {
                    $orderTotal -= $order->getShippingAmount();
                    if ($ga4Helper->excludeShippingFromTransactionIncludingTax()) {
                        $orderTotal -= $order->getShippingTaxAmount();
                    }
                }
                break;
        }

        return $orderTotal;
    }

    /**
     * @param $order
     * @param $ga4Helper
     * @return float
     */
    public function calculateBaseOrderTotal($order, $ga4Helper)
    {
        $orderTotalCalculationOption = $ga4Helper->getOrderTotalCalculation();
        switch ($orderTotalCalculationOption) {
            case \WeltPixel\GA4\Model\Config\Source\OrderTotalCalculation::CALCULATE_SUBTOTAL:
                $orderTotal = $order->getBaseSubtotal();
                break;
            case \WeltPixel\GA4\Model\Config\Source\OrderTotalCalculation::CALCULATE_GRANDTOTAL:
            default:
                $orderTotal = $order->getBaseGrandtotal();
                if ($ga4Helper->excludeTaxFromTransaction()) {
                    $orderTotal -= $order->getBaseTaxAmount();
                }

                if ($ga4Helper->excludeShippingFromTransaction()) {
                    $orderTotal -= $order->getBaseShippingAmount();
                    if ($ga4Helper->excludeShippingFromTransactionIncludingTax()) {
                        $orderTotal -= $order->getBaseShippingTaxAmount();
                    }
                }
                break;
        }

        return $orderTotal;
    }

    /**
     * @param $order
     * @param $ga4Helper
     * @return float
     */
    public function calculateAddonOrderTotal($order, $ga4Helper) {
        $addonOrderTotalCalculation = $ga4Helper->getAddonOrderTotalCalculation();
        switch ($addonOrderTotalCalculation) {
            case \WeltPixel\GA4\Model\Config\Source\AddonOrderTotalCalculation::CALCULATE_SUBTOTAL:
                $orderTotal = $order->getSubtotal();
                break;
            case \WeltPixel\GA4\Model\Config\Source\AddonOrderTotalCalculation::CALCULATE_GRANDTOTAL:
            default:
                $orderTotal = $order->getGrandtotal();
                if ($ga4Helper->excludeAddonTaxFromTransaction()) {
                    $orderTotal -= $order->getTaxAmount();
                }

                if ($ga4Helper->excludeAddonShippingFromTransaction()) {
                    $orderTotal -= $order->getShippingAmount();
                    if ($ga4Helper->excludeAddonShippingFromTransactionIncludingTax()) {
                        $orderTotal -= $order->getShippingTaxAmount();
                    }
                }
                break;
        }
        return $orderTotal;
    }
}
