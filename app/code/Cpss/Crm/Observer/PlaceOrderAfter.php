<?php

namespace Cpss\Crm\Observer;

use Cpss\Crm\Model\CpssApiRequest;
use Magento\Framework\Event\ObserverInterface;
use Cpss\Crm\Model\CalcPointDiscount;

class PlaceOrderAfter implements ObserverInterface
{
    protected $session;
    protected $customerSession;
    protected $quoteRepository;
    protected $cpssApiRequest;
    protected $customerHelper;

    /**
     * @var \OnitsukaTigerCpss\Crm\Helper\HelperData
     */
    protected $helper;

    public function __construct(
        \Magento\Checkout\Model\Session $session,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest,
        \Cpss\Crm\Helper\Customer $customerHelper,
        \OnitsukaTigerCpss\Crm\Helper\HelperData $helper,
        protected CalcPointDiscount $calcPointDiscount
    ) {
        $this->session = $session;
        $this->customerSession = $customerSession;
        $this->cpssApiRequest = $cpssApiRequest;
        $this->quoteRepository = $quoteRepository;
        $this->customerHelper = $customerHelper;
        $this->helper = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/PlaceOrderAfter.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Place Order After Start============================');
        if ($this->customerHelper->isModuleEnabled()) {
            //Used Points & Acquired Points Quote to Order
            $order = $observer->getEvent()->getOrder();
            $quote = $this->quoteRepository->get($order->getQuoteId());

            $memberId = $this->customerSession->getMemberId();
            $usedPoint = $quote->getUsedPoint();
            $usePointItemArray = [];

            $points_to_earn = $quote->getAcquiredPoint();

            if (!empty($order->getCouponCode()) || empty($usedPoint)) {
                $getPointRateResult = $this->cpssApiRequest->getPointRate($quote->getCustomerId());
                $pointGrantRate = $getPointRateResult['rate'];

                $subTotalInclTax = 0;
                $taxPercent = 10;
                $totalDiscount = 0;

                foreach ($quote->getAllVisibleItems() as $items) {
                    $subTotalInclTax += ($items->getBasePriceInclTax() * $items->getQty());
                    $totalDiscount += $items->getDiscountAmount();
                    $taxPercent = (int)$items->getTaxPercent();
                }

                $tax = (1 + ($taxPercent/100));

                $orderAmount = $subTotalInclTax - $totalDiscount;
                $totalPointEarningAmount = $orderAmount / $tax;
                $points_to_earn = (int)(($totalPointEarningAmount * $this->helper->getPointEarnedMultiplyBy()) * $pointGrantRate);
            } else {
                foreach ($quote->getAllVisibleItems() as $items) {
                    $usePointItemArray[$items->getSku()] = $items->getUsedPoint();
                    $logger->info('Item Array: ' . print_r($usePointItemArray, true));
                    $logger->info('Quote Item: ' . print_r(json_decode(json_encode($items->getData())), true));
                }
            }


            $order->setUsedPoint($usedPoint);
            $order->setAcquiredPoint($points_to_earn);
            $order->setHowToUse($quote->getHowToUse());
            $order->setMemberId($memberId);
            $order->setNonProportionablePoint($quote->getNonProportionablePoint());

            //Sub Point
//            if ($usedPoint > 0) {
//                $response = $this->cpssApiRequest->usePoint($order->getIncrementId(), $memberId, $usedPoint);
//                $order->setCpssSubStatus($response['X-CPSS-Result']);
//            }

            if ($usedPoint > 0) {
                $quoteDetails = [];
                $usedPoints = $this->helper->calculateUsedPointDiscount($quote->getUsedPoint());
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
                $oItems = $order->getAllVisibleItems();
                foreach ($oItems as $oItem) {
                    $itemDiscountAmount = $oItem->getDiscountAmount();
                    $itemBaseDiscountAmount = $oItem->getDiscountAmount();
                    $usedPointDiscount = (isset($usePointItemArray[$oItem->getSku()])) ? $usePointItemArray[$oItem->getSku()] : 0;
                    $logger->info("Item Discount Amount: " . $itemDiscountAmount);
                    $logger->info("Item Base Discount Amount: " . $itemBaseDiscountAmount);
                    $logger->info("Used Point Discount: " . $usedPointDiscount);
                    $logger->info("Used Point: " . $usedPoint);
                    $logger->info("Calculated Order Detail: " . print_r($calculatedOrderDetail, true));
                    $usedPointItem = $calculatedOrderDetail[$oItem->getSku()]['discountAmount'];
                    $oItem->setUsedPoint($usedPointItem);

                    if ($usedPointDiscount > 0 && ($itemDiscountAmount <= 0 || !empty($order->getCouponCode()))) {
                        $finalItemDiscountAmount = $itemDiscountAmount + $usedPointDiscount;
                        $finalItemBaseDiscountAmount = $itemBaseDiscountAmount + $usedPointDiscount;
                        $logger->info("Final Discount Amount: " . $finalItemDiscountAmount);
                        $logger->info("Final Base Discount Amount: " . $finalItemBaseDiscountAmount);
                        $oItem->setDiscountAmount($finalItemDiscountAmount);
                        $oItem->setBaseDiscountAmount($finalItemBaseDiscountAmount);
                    }
                }
            }

            $logger->info('==========================Place Order After End============================');

            // Reset Session
            $this->session->setAppliedPoints(0);
            $this->session->setHowToUse(null);
        }

        return $this;
    }
}

