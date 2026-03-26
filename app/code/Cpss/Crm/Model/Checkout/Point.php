<?php

namespace Cpss\Crm\Model\Checkout;

use Cpss\Crm\Api\Checkout\PointInterface;
use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\CpssApiRequest;
use Magento\Checkout\Model\Session;
use Cpss\Crm\Model\CalcPointDiscount;

class Point implements PointInterface
{
    protected $checkoutSession;
    protected $logger;
    protected $cpssApiRequest;
    protected $calcPointDiscount;

    /**
     * @var \OnitsukaTigerCpss\Crm\Helper\HelperData
     */
    protected $helper;

    public function __construct(
        Session $checkoutSession,
        Logger $logger,
        CpssApiRequest $cpssApiRequest,
        \OnitsukaTigerCpss\Crm\Helper\HelperData $helper,
        CalcPointDiscount $calcPointDiscount
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->cpssApiRequest = $cpssApiRequest;
        $this->helper = $helper;
        $this->calcPointDiscount = $calcPointDiscount;
    }

    /**
     * {@inheritdoc}
     */
    public function set($point)
    {
        try {
            $data = json_decode($point);
            $quote = $this->checkoutSession->getQuote();

            $usedPoints = $this->helper->calculateUsedPointDiscount($data->point);
            $getPointRateResult = $this->cpssApiRequest->getPointRate($quote->getCustomerId());
            $pointGrantRate = $getPointRateResult['rate'];

            $this->checkoutSession->setAppliedPoints($usedPoints);
            $this->checkoutSession->setHowToUse($data->how_to_use);

            $subTotalInclTax = 0;
            $taxPercent = 10;
            $totalDiscount = 0;

            foreach ($quote->getAllVisibleItems() as $items) {
                $subTotalInclTax += ($items->getBasePriceInclTax() * $items->getQty());
                $totalDiscount += $items->getDiscountAmount();
                $taxPercent = (int)$items->getTaxPercent();
            }

            $tax = (1 + ($taxPercent/100));

            $orderAmount = $subTotalInclTax - $totalDiscount - $usedPoints;
            $totalPointEarningAmount = $orderAmount / $tax;
            $points_to_earn = (int)(($totalPointEarningAmount * $this->helper->getPointEarnedMultiplyBy()) * $pointGrantRate);

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/acquiredPoint.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('================Acquired Point Start==================');
            $logger->info('Quote Discount: ' . $quote->getDiscountAmount());
            $logger->info('Subtotal Inc Tax: ' . $subTotalInclTax);
            $logger->info('Total Discount: ' . $totalDiscount);
            $logger->info('Used Points: ' . $usedPoints);
            $logger->info('Order Amount: ' . $orderAmount);
            $logger->info('Tax: ' . $tax);
            $logger->info('Total Earning Points: ' . $totalPointEarningAmount);
            $logger->info('Point Earned Multiply By: ' . $this->helper->getPointEarnedMultiplyBy());
            $logger->info('Point Grant Rate: ' . $pointGrantRate);
            $logger->info('Acquired Points: ' . $points_to_earn);

            $logger->info('================Acquired Point End==================');

            $quote->setUsedPoint($data->point);
            $quote->setAcquiredPoint($points_to_earn);
            $quote->setHowToUse($data->how_to_use);
            $quote->save();

            return 'OK';
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($point)
    {
        try {
            $data = json_decode($point);
            $this->checkoutSession->setAppliedPoints(0);
            $this->checkoutSession->setHowToUse();
            $quote = $this->checkoutSession->getQuote();
            $usedPoints = $quote->getUsedPoint();
            $quote->setUsedPoint(0);
            $quote->setAcquiredPoint($data->points_to_earn);
            $quote->setHowToUse();
            $quote->save();

            $quoteDetails = [];
            $taxPercent = 0;

            foreach ($quote->getAllVisibleItems() as $item) {
                $quoteDetails[] = [
                    "sku" => $item->getSku(),
                    "qty" => (int)$item->getQty(),
                    "unitPriceExTax" => (int)$item->getPrice(),
                    "rowTotalInclTax" => $item->getRowTotalInclTax(),
                    "grandTotal" => $quote->getGrandTotal(),
                    "discountAmount" => $item->getDiscountAmount()

                ];

                $taxPercent = $item->getTaxPercent();
            }

            $calculatedOrderDetail = $this->calcPointDiscount->calculateOrderDetails($quoteDetails, $taxPercent, (int)$usedPoints);
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/removePoint.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('==========================Quote Data Debugging Start============================');
            $logger->info('Calculated Order Details:' . print_r($calculatedOrderDetail, true));
            $logger->info('Quote ID: ' . $quote->getId());
            $logger->info('Used Poinit: ' . $usedPoints);
            foreach ($quote->getAllVisibleItems() as $item) {
                if (!empty($calculatedOrderDetail)) {
                    $logger->info('Remove Point Item Array: ' . print_r($calculatedOrderDetail[$item->getSku()], true));
                    $usedPointItem = $calculatedOrderDetail[$item->getSku()]['discountAmount'];
                    $logger->info('Remove Point Item: ' . $usedPointItem);
                    $itemDiscountAmount = $item->getDiscountAmount();
                    $itemBaseDiscountAmount = $item->getBaseDiscountAmount();
                    $logger->info('Before Remove Point Item: ' . $item->getUsedPoint());
                    $item->setDiscountAmount($itemDiscountAmount - $usedPointItem);
                    $item->setBaseDiscountAmount($itemBaseDiscountAmount - $usedPointItem);
                    $item->setUsedPoint(0);

                    $item->save();
                }
            }

            return "OK";
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEarnedPoints()
    {
        return $this->calculateEarnedPoints();
    }

    public function calculateEarnedPoints($order = null)
    {
        $subTotal = 0;
        $cart = $this->checkoutSession->getQuote();
        $used_points = (int)$this->checkoutSession->getAppliedPoints();

        $used_points = $this->helper->calculateUsedPointDiscount($used_points);

        $getPointRateResult = $this->cpssApiRequest->getPointRate($cart->getCustomerId());
        $pointGrantRate = $getPointRateResult['rate'];
        $oddValue = $getPointRateResult['odd'];

        if ($order == null) {
            foreach ($cart->getAllVisibleItems() as $items) {
                $subTotal += $items->getRowTotal();
            }
        } else {
            $subTotal = $order->getSubTotal();
        }

        $acquired_points = ($subTotal - $used_points) * $pointGrantRate;

        if (empty($oddValue)) { //★Add, cpss default
            $oddValue == 0;
            $this->logger->error("no oddValue. set default = 0");
        }

        if ($oddValue == 0) {
            $acquired_points = (int)($acquired_points);
        } elseif ($oddValue == 1) {
            $acquired_points = (int)($acquired_points);
        } elseif ($oddValue == 2) {
            $acquired_points = (int)($acquired_points);
        } else { //★Add
            $this->logger->error("oddValue " . $oddValue . " is not expected. set default = 0");
            $acquired_points = (int)($acquired_points);
        }

        return $acquired_points;
    }
}

