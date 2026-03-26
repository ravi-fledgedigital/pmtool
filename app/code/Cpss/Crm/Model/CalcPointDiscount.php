<?php
namespace Cpss\Crm\Model;

class CalcPointDiscount
{
    protected int $_pointDiscountAmount;
    protected float $_taxRate;
    protected array $_orderData;
    protected array $_orderDetails;
    protected array $_discountedOrderData;
    protected array $_discountedOrderDetails;
    protected int $_nonProportionablePoint;

    /**
     * Calculate order details.
     *
     * @param array $orderDetails.
     * @param int $taxPercentage.
     * @param int $usedPoints.
     *
     * @return array.
     */
    public function calculateOrderDetails($orderDetails, $taxPercentage, $usedPoints)
    {
        $this->_taxRate = $taxPercentage / 100;
        $this->_pointDiscountAmount = $usedPoints;
        $this->_nonProportionablePoint = 0;

        $this->setOrder($orderDetails);
        $this->createDiscountOrder();

        return $this->getDiscountedOrderAmountPerSku($this->getDiscountedOrder());
    }

    /**
     * Get Discounted Order Amount Per Sku.
     *
     * @param array $discountedOrderArray.
     *
     * @return array.
     */
    public function getDiscountedOrderAmountPerSku($discountedOrderArray)
    {
        $discountAmountArray = [];

        $discountAmountArray['nonProportionalbePoint'] = $discountedOrderArray['summary']['nonProportionalbePoint'];
        foreach ($discountedOrderArray['details'] as $detail) {
            $discountAmountArray[$detail['sku']] = $detail;//$detail['discountAmount'];
        }

        return $discountAmountArray;
    }

    /**
     * Set point discount.
     *
     * @param int $discountAmount Point discount amount.
     */
    public function setPointDiscount(int $discountAmount)
    {
        $this->_pointDiscountAmount = $discountAmount;
    }

    /**
     * Set order.
     *
     * @param array $orderDetails order details set.
     * Structure as below.
     * {{"sku" => sku, "qty" => order quantity, "unitPriceExTax" => unit product price exclude tax}, {...}, {...}}
     */
    public function setOrder(array $orderDetails)
    {
        if (empty($orderDetails)) {
            print("Order data is empty. Do nothing.\n");
            return;
        }
        foreach ($orderDetails as &$data) {
            if (gettype($data) !== "array") {
                print("Order structure is invalid. Do nothing.\n");
                return;
            }
            if ((!isset($data["sku"]) || gettype($data["sku"]) !== "string")
                || (!isset($data["qty"]) || gettype($data["qty"]) !== "integer")
                || (!isset($data["unitPriceExTax"]) || gettype($data["unitPriceExTax"]) !== "integer")) {
                print("Order elements is invalid. Do nothing.\n");
                return;
            }
            if (($data["qty"] <= 0) || ($data["unitPriceExTax"] <= 0)) {
                print("Order number is invalid. Do nothing.\n");
                return;
            }
            $data["subTotalExTax"] = $data["unitPriceExTax"] * $data["qty"];
            $data["subTotalTaxAmount"] = round($data["subTotalExTax"] * $this->_taxRate);
            $data["subTotalIncTax"] = $data["subTotalExTax"] + $data["subTotalTaxAmount"];
        }
        unset($data);
        //Sort order details array with "sku" ASC.
        array_multisort(array_column($orderDetails, "sku"), SORT_ASC, $orderDetails);
        $this->_orderDetails = $orderDetails;
        $this->_orderData = $this->createOrderSummary($this->_orderDetails);
        $this->_nonProportionablePoint = 0;
    }

    /**
     * Calc discount price per products.
     *
     * Based on point discount will apply at total order amount include tax.
     *
     * Logic as below.
     * product discount price = round(discount price * product unit price include tax / total order amount include tax)
     * (This is calculated per product unit.)
     * May be it has round error about divided discount amount.
     * If total(divided discount amount) is not equal point discount amount, adjust discount amount.
     * Calc again tax amount and exclude discounted price each products.
     */
    public function createDiscountOrder()
    {
        $totalAmountIncTax = $this->_orderData["grandTotal"];
        if ($totalAmountIncTax < $this->_pointDiscountAmount) {
            // print("point discount amount(=".$this->_pointDiscountAmount.") > total amount include tax(=".$totalAmountIncTax.")\n");
            //print("point discount amount is reduced as ".$totalAmountIncTax."\n");
            $this->_pointDiscountAmount = $totalAmountIncTax;
        }
        $pointDiscountAmount = $this->_pointDiscountAmount;
        $discountedOrderAmount = $totalAmountIncTax - $pointDiscountAmount;
        $discountResult = [];
        $sumOfDividedDiscountAmount = 0;
        $sumDiscounts = 0;
        foreach ($this->_orderDetails as $index => $skuData) {
            $subTotalIncTax = $skuData["rowTotalInclTax"];
            $currentDiscountAmount = $pointDiscountAmount * $subTotalIncTax / $totalAmountIncTax;
            if ($index < count($this->_orderDetails) - 1) {
                $sumDiscounts += round($currentDiscountAmount, 2);
            } else {
                $currentDiscountAmount = $pointDiscountAmount - $sumDiscounts;
                /*if ($currentDiscountAmount > $lastItemDiscount) {
                    $currentDiscountAmount = $lastItemDiscount;
                }*/
            }
            $currentDiscountAmount = round($currentDiscountAmount, 2);
            $discountedSubTotalIncTax = $subTotalIncTax - $currentDiscountAmount - $skuData['discountAmount'];
            $sumOfDividedDiscountAmount += $currentDiscountAmount;

            $discountResult[] = ["sku" => $skuData["sku"],
                "qty" => $skuData["qty"],
                "subTotalExTax" => $skuData["subTotalExTax"],
                "subTotalIncTax" => $subTotalIncTax,
                "discountAmount" => $currentDiscountAmount,
                "discountedSubTotalIncTax" => $discountedSubTotalIncTax,
                "returnQty" => 0,
                "returnPoint" => 0,
                "discountedSubTotalTaxAmount" => 0,
                "discountedSubTotalExTax" => 0,
            ];

        }
        // If it has rounding error, it's need to adjust discount amount.
        if ($sumOfDividedDiscountAmount != $pointDiscountAmount) {
            $adjustAmount = $pointDiscountAmount - $sumOfDividedDiscountAmount;
            //print("It's need to adjust amount because of rounding error. adjust amount = ".$adjustAmount."\n");
            //$discountResult = $this->addAdjustAmount($discountResult, $adjustAmount);
            // Calc discounted product tax and discounted price exclude tax.

        }
        foreach ($discountResult as &$skuInfo) {
            $skuInfo["discountedSubTotalTaxAmount"] = round($skuInfo["discountedSubTotalIncTax"] / (1 + $this->_taxRate) * $this->_taxRate, 2);
            $skuInfo["discountedSubTotalExTax"] = $skuInfo["discountedSubTotalIncTax"] - $skuInfo["discountedSubTotalTaxAmount"];
        }

        unset($skuInfo);
        $this->_discountedOrderDetails = $discountResult;

        $this->calcDiscountedTotalAmount();
    }

    /**
     * Get discounted order data(Summary and details).
     */
    public function getDiscountedOrder()
    {
        return ["summary" => $this->_discountedOrderData,
            "details" => $this->_discountedOrderDetails];
    }

    /**
     * Display discount result.
     */
    public function displayDiscountResult()
    {
        print("Order summary.\n");
        print("---- begin ----\n");
        print_r($this->_orderData);
        print("----- end -----\n");

        print("\nOrder details.\n");
        print("---- begin ----\n");
        print_r($this->_orderDetails);
        print("----- end -----\n");

        print("\n*** Point discount amount = " . $this->_pointDiscountAmount . " ***\n\n");
        print("Order summary with discount.\n");
        print("---- begin ----\n");
        print_r($this->_discountedOrderData);
        print("----- end -----\n");

        print("\nOrder details with point discount.\n");
        print("---- begin ----\n");
        print_r($this->_discountedOrderDetails);
        print("----- end -----\n");
    }

    /**
     * Add adjust amount to discount result.
     *
     * @param array $discountResult Discount result for each products.
     * @param mixed $adjustAmount Adjust amount about discount.
     *
     * @return discount result which is added adjust amount.
     */
    private function addAdjustAmount(array $discountResult, float $adjustAmount)
    {
        if ($adjustAmount == 0) {
            //Adjust amount is 0. No need to adjust.
            return $discountResult;
        }

        $minAdjustAmount = ($adjustAmount > 0) ? 1 : -1;
        while ($adjustAmount != 0) {
            $adjusted = false;
            foreach ($discountResult as &$skuInfo) {
                //Do add adjust if adjusted discount product amount >= 0 and adjusted discount price >= 0.
                if (($skuInfo["discountedSubTotalIncTax"] - $minAdjustAmount < 0)
                    || ($skuInfo["discountAmount"] + $minAdjustAmount < 0)) {
                    // Sku cannot adjust.
                    continue;
                }
                $adjusted = true;
                $skuInfo["discountedSubTotalIncTax"] -= $minAdjustAmount;
                $skuInfo["discountAmount"] += $minAdjustAmount;
                $adjustAmount -= $minAdjustAmount;
                if ($adjustAmount == 0) {
                    break;
                }
            }
            unset($skuInfo);
            if ($adjusted === false) {
                //print("It cannot add more adjust amount, but adjustAmount = ".$adjustAmount." is still remains.\n");
                $this->_nonProportionablePoint = $adjustAmount;
                break;
            }
        }

        return $discountResult;
    }

    /**
     * Calc discount price for order.
     */
    private function calcDiscountedTotalAmount()
    {
        $pointDiscountAmount = $this->_pointDiscountAmount;
        $totalDiscountedAmountIncTax = $this->_orderData["totalAmountIncTax"] - $pointDiscountAmount;
        $totalDiscountedTaxAmount = round($totalDiscountedAmountIncTax / (1 + $this->_taxRate) * $this->_taxRate);
        $totalDiscountedAmountExTax = $totalDiscountedAmountIncTax - $totalDiscountedTaxAmount;
        $discountedOrderData = [
            "totalAmountIncTax" => $this->_orderData["totalAmountIncTax"],
            "pointDiscountAmount" => $pointDiscountAmount,
            "totalDiscountedAmountExTax" => $totalDiscountedAmountExTax,
            "totalDiscountedTaxAmount" => $totalDiscountedTaxAmount,
            "totalDiscountedAmountIncTax" => $totalDiscountedAmountIncTax,
            "nonProportionalbePoint" => $this->_nonProportionablePoint
        ];
        $this->_discountedOrderData = $discountedOrderData;
    }

    /**
     * Create order summary.
     */
    private function createOrderSummary($orderDetails)
    {
        $totalAmountExTax = array_sum(array_column($orderDetails, "subTotalExTax"));
        $totalTax = round($totalAmountExTax * $this->_taxRate);
        $totalAmountIncTax = $totalAmountExTax + $totalTax;
        $grandTotal = array_sum(array_column($orderDetails, "rowTotalInclTax"));

        return ["grandTotal" => $grandTotal, "totalAmountExTax" => $totalAmountExTax, "totalTax" => $totalTax, "totalAmountIncTax" => $totalAmountIncTax];
    }
}
