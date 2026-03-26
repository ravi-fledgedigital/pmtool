<?php

namespace OnitsukaTigerCpss\Crm\Observer;

use Cpss\Crm\Model\CalcPointDiscount;
use Cpss\Crm\Model\PointConfigProvider;
use Magento\Framework\Event\Observer;
use Magento\Framework\UrlInterface;
use OnitsukaTigerCpss\Crm\Helper\Data;

class SavePoints extends \Cpss\Crm\Observer\SavePoints
{
    protected $helperData;

    /**
     * @var \OnitsukaTigerCpss\Crm\Helper\HelperData
     */
    protected $helper;

    public function __construct(
        PointConfigProvider $pointConfigProvider,
        CalcPointDiscount $calcPointDiscount,
        UrlInterface $url,
        Data $helperData,
        \OnitsukaTigerCpss\Crm\Helper\HelperData $helper
    ) {
        $this->helperData = $helperData;
        $this->helper = $helper;
        parent::__construct($pointConfigProvider, $calcPointDiscount, $url);
    }

    public function execute(Observer $observer)
    {
        $isModuleEnabled = $this->helperData->isModuleEnabled();
        if (!$isModuleEnabled) {
            return;
        }
        $quote = "";
        $total = "";

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $checkoutSession = $objectManager->create(\Magento\Checkout\Model\Session::class);

        if (!preg_match('/checkout\/cart\/add/', $this->url->getCurrentUrl())) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('CPSS SavePoints sales_quote_address_collect_totals_after');
            $quote = $observer->getData('quote');
            $total = $observer->getData('total');

            $usedPoints = $this->helper->calculateUsedPointDiscount($quote->getUsedPoint());

            $grandTotal = $total->getGrandTotal();
            $baseGrandTotal = $total->getBaseGrandTotal();
            $quoteDetails = [];
            $taxPercent = 0;

            foreach ($quote->getAllVisibleItems() as $item) {
                if ($usedPoints >= 0 && $baseGrandTotal > 0) {
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
            }

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/save_point.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('==========================Quote Data Debugging Start============================');
            $logger->info('Used Points: ' . $usedPoints);
            $logger->info('Quote Details: ' . print_r($quoteDetails, true));
            $logger->info('Tax Percent: ' . $taxPercent);
            $logger->info('Tax Percent: ' . $taxPercent);
            $logger->info('URL: ' . $this->url->getCurrentUrl());
            $logger->info('Checkout Session Details: ' . $checkoutSession->getIsPointUsed());

            if (preg_match('/carts\/mine\/payment-information/', $this->url->getCurrentUrl())) {
                if (!$checkoutSession->getIsPointUsed()) {
                    $checkoutSession->setIsPointUsed(1);
                } else {
                    $totalCheckout = $checkoutSession->getIsPointUsed();
                    $totalCheckout++;
                    $checkoutSession->setIsPointUsed($totalCheckout);
                }
            } else {
                $checkoutSession->setIsPointUsed(1);
            }

            $totalTaxAmount = 0;

            if ($usedPoints > 0 && !empty($quoteDetails) && $checkoutSession->getIsPointUsed() <= 2) {
                $checkoutSession->unsIsPointSet();
                $calculatedOrderDetail = $this->calcPointDiscount->calculateOrderDetails($quoteDetails, $taxPercent, (int)$usedPoints);
                $logger->info('==========================Save Point Discount Log Start============================');
                foreach ($quote->getAllVisibleItems() as $item) {
                    if (!empty($calculatedOrderDetail)) {
                        $logger->info('Used Point Item Array: ' . print_r($calculatedOrderDetail[$item->getSku()], true));
                        $usedPointItem = $calculatedOrderDetail[$item->getSku()]['discountAmount'];
                        $logger->info('Used Point Item: ' . $usedPointItem);
                        $itemDiscountAmount = $item->getDiscountAmount();
                        $itemBaseDiscountAmount = $item->getBaseDiscountAmount();
                        $logger->info('Before Used Point Item: ' . $item->getUsedPoint());
                        $item->setDiscountAmount($itemDiscountAmount + $usedPointItem);
                        $item->setBaseDiscountAmount($itemBaseDiscountAmount + $usedPointItem);
                        $item->setUsedPoint($usedPointItem);

                        if (
                            isset($calculatedOrderDetail[$item->getSku()]['discountedSubTotalTaxAmount']) &&
                            $calculatedOrderDetail[$item->getSku()]['discountedSubTotalTaxAmount'] > 0
                        ) {
                            $tAmount = $calculatedOrderDetail[$item->getSku()]['discountedSubTotalTaxAmount'];
                            $itemCompensationAmount = $item->getTaxAmount() - $tAmount;
                            $item->setTaxAmount($tAmount);
                            $item->setBaseTaxAmount($tAmount);

                            $logger->info('======================================================');
                            $logger->info('Calculated Order Details: ' . print_r($calculatedOrderDetail[$item->getSku()], true));
                            $logger->info('Tax Amount: ' . $item->getTaxAmount());
                            $logger->info('T Amount: ' . $tAmount);

                            if ($itemCompensationAmount > 0) {
                                $itemCompensationAmount = $itemCompensationAmount + $item->getDiscountTaxCompensationAmount();
                                $item->setDiscountTaxCompensationAmount($itemCompensationAmount);
                                $item->setBaseDiscountTaxCompensationAmount($itemCompensationAmount);
                            }
                            $logger->info('Item Compensation Amount: ' . $itemCompensationAmount);
                            $totalTaxAmount += $tAmount;
                            $logger->info('Total Tax Amount: ' . $totalTaxAmount);
                        }

                        $logger->info('======================================================');
                        $logger->info('Used Point Item: ' . $usedPointItem);
                        $logger->info('Item Discount: ' . $itemDiscountAmount);
                        $logger->info('Item Base Discount Amount: ' . $itemBaseDiscountAmount);
                        $logger->info('After Set Item Discount: ' . $item->getDiscountAmount());
                        $logger->info('After Set Item Base Discount Amount: ' . $item->getBaseDiscountAmount());
                        $logger->info('======================================================');

                        $item->save();
                    }
                }

                $logger->info('==========================Save Point Discount Log Start============================');

                $quote->setNonProportionablePoint($calculatedOrderDetail['nonProportionalbePoint']);
                //$quote->save(); //can cause deadlock issue
            }

            if ($grandTotal != 0 && $baseGrandTotal != 0) {
                $discountAmount = $total->getDiscountAmount();
                $baseDiscountAmount = $total->getBaseDiscountAmount();
                $subtotalWithDiscount = $total->getSubtotalWithDiscount();
                $baseSubtotalWithDiscount = $total->getBaseSubtotalWithDiscount();

                $logger->info('Discount Amount: ' . $discountAmount);
                $logger->info('Base Discount Amount: ' . $baseDiscountAmount);
                $logger->info('Subtotal With Discount: ' . $subtotalWithDiscount);
                $logger->info('Base Subtotal With Discount: ' . $baseSubtotalWithDiscount);
                $logger->info('Used Points: ' . $usedPoints);
                $logger->info('Total Tax Amount: ' . $totalTaxAmount);
                $logger->info('Grand Total: ' . $grandTotal);
                $logger->info('Base Grand Total: ' . $baseGrandTotal);

                if ($usedPoints > 0 && $totalTaxAmount > 0) {
                    $compensationAmount = $total->getTaxAmount() - $totalTaxAmount;
                    $total->setTaxAmount($totalTaxAmount);
                    $total->setBaseTaxAmount($totalTaxAmount);
                    if ($compensationAmount > 0) {
                        $compensationAmount = $compensationAmount + $total->getDiscountTaxCompensationAmount();
                        $total->setDiscountTaxCompensationAmount($compensationAmount);
                        $total->setBaseDiscountTaxCompensationAmount($compensationAmount);
                        $logger->info('Item Compensation Amount: ' . $discountAmount);
                    }
                }

                $logger->info('Total Discount Amount: ' . abs($discountAmount) + $usedPoints);
                $logger->info('Total Base Discount Amount: ' . abs($baseDiscountAmount) + $usedPoints);

                $total->setDiscountAmount(abs($discountAmount) + $usedPoints);
                $total->setBaseDiscountAmount(abs($baseDiscountAmount) + $usedPoints);
                $total->setGrandTotal($grandTotal - $usedPoints);
                $total->setBaseGrandTotal($baseGrandTotal - $usedPoints);

                $total->setSubtotalWithDiscount($subtotalWithDiscount - $usedPoints);
                $total->setBaseSubtotalWithDiscount($baseSubtotalWithDiscount - $usedPoints);
            }

            $logger->info('==========================Quote Data Debugging End============================');
            $logger->info('======================================================');
            $logger->info('======================================================');
        } else {
            $checkoutSession->unsIsPointUsed();
        }
    }
}
