<?php
namespace Cpss\Crm\Model;

use Cpss\Crm\Helper\Customer;
use Cpss\Crm\Logger\Logger;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\ScopeInterface;

class PointConfigProvider implements ConfigProviderInterface
{
    const TAX_RATE = 10;

    public $customerSession;
    protected $checkoutSession;
    protected $cpssApiRequest;
    protected $customerHelper;
    protected $scopeConfig;
    protected $shopReceipt;
    protected $logger;
    protected $pointRate;
    protected $http;

    /**
     * @var \OnitsukaTigerCpss\Crm\Helper\HelperData
     */
    protected $helper;

    protected $taxRate = 0;

    public function __construct(
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        CpssApiRequest $cpssApiRequest,
        Customer $customerHelper,
        ScopeConfigInterface $scopeConfig,
        ShopReceipt $shopReceipt,
        Logger $logger,
        Http $http,
        \OnitsukaTigerCpss\Crm\Helper\HelperData $helper
    ) {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->cpssApiRequest = $cpssApiRequest;
        $this->customerHelper = $customerHelper;
        $this->scopeConfig = $scopeConfig;
        $this->shopReceipt = $shopReceipt;
        $this->logger = $logger;
        $this->http = $http;
        $this->pointRate = [];
        $this->helper = $helper;
    }

    public function getConfig()
    {
        $isModuleEnabled = $this->helper->isEnableModule();
        $currentPoints = 0;
        $acquiredPoints = 0;
        $howToUse = null;
        $appliedPoints = 0;
        $enablePointCheckoutPage = false;
        $isLoggedIn = $this->customerSession->isLoggedIn();

        if ($isModuleEnabled) {
            $memberId = $this->customerHelper->getCpssMembeIdPrefix() . $this->customerSession->getCustomerId();
            $this->customerHelper->setMemberId($memberId);
            $currentPoints = $this->getCurrentPoints();
            $acquiredPoints = $this->calcAcquiredPoints();
            $quote = $this->checkoutSession->getQuote();
            $appliedPoints = $quote->getUsedPoint();
            $howToUse = $quote->getHowToUse();
            $enablePointCheckoutPage = ($this->scopeConfig->getValue('crm/general/enable_point_checkout_page', ScopeInterface::SCOPE_STORE)) ? true : $enablePointCheckoutPage;
        }

        $this->checkoutSession->setCurrentPoints($currentPoints);

        $config = [];
        $config['currentPoints'] = $currentPoints;
        $config['points_to_earn'] = $acquiredPoints;
        $config['appliedPoints'] = $appliedPoints;
        $config['howtouse'] = $howToUse;
        $config['enabled'] = $isModuleEnabled;
        $config['enablePointCheckoutPage'] = $enablePointCheckoutPage;
        $config['point_grant_rate'] = $this->getPointGrantRate($this->customerSession->getMemberId())['rate'] ?? 1;
        $config['point_odd'] = $this->getPointGrantRate($this->customerSession->getMemberId())['odd'] ?? 0;
        $config['tax_rate'] = ($this->taxRate/100);
        $config['point_rate'] = (!empty($this->helper->getPerXRateValue())) ? $this->helper->getPerXRateValue() : 1;
        $config['per_point'] = (!empty($this->helper->getPerXPointValue())) ? $this->helper->getPerXPointValue() : 100;
        $config['point_multiply_by'] = (!empty($this->helper->getPointEarnedMultiplyBy())) ? $this->helper->getPointEarnedMultiplyBy() : 100;

        return $config;
    }

    public function calcAcquiredPoints($quote = null)
    {
        $subTotalInclTax = 0;
        $acquired_points = 0;
        $taxPercent = 10;
        $totalDiscount = 0;
        if ($quote == null) {
            $quote = $this->checkoutSession->getQuote();
        }

        $getPointRateResult = $this->getPointGrantRate($this->customerSession->getMemberId());
        $pointGrantRate = $getPointRateResult['rate'];
        $oddValue = $getPointRateResult['odd'];

        foreach ($quote->getAllVisibleItems() as $items) {
            // Excluding tax
            // $itemTotalInclTax = ($items->getBasePriceInclTax() * $items->getQty());
            // if(!((int)$itemTotalInclTax == (int)$items->getUsedPoint())){
            //     $acquired_points += ($items->getBasePrice() * $items->getQty()) * $pointGrantRate;
            // }
            // Including Tax
            // $acquired_points += ($items->getBasePriceInclTax() * $items->getQty()) * $pointGrantRate;

            $subTotalInclTax += ($items->getBasePriceInclTax() * $items->getQty());
            $totalDiscount += $items->getDiscountAmount();
            $taxPercent = (int)$items->getTaxPercent();
        }

        $this->taxRate = $taxPercent;

        // followed checkout confirmation acquired_points calculation
        // app/code/Cpss/Crm/view/frontend/web/js/view/payment/point.js:getPointsToBeEarned
        $tax = (1 + ($taxPercent/100));
        /*$usedPoints = $this->helper->calculateUsedPointDiscount($quote->getUsedPoint());*/

        $discountedTotalAmountExcludeTax = $subTotalInclTax - $totalDiscount;
        $totalPointEarningAmount = $discountedTotalAmountExcludeTax / $tax;

        $acquired_points = (int)(($totalPointEarningAmount * $this->helper->getPointEarnedMultiplyBy()) * $pointGrantRate);

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/acquiredPoint.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('================Acquired Point Config Provider Start==================');
        $logger->info('Subtotal Inc Tax: ' . $subTotalInclTax);
        $logger->info('Total Discount: ' . $totalDiscount);
        $logger->info('Discounted Total Amount Exclude Tax: ' . $discountedTotalAmountExcludeTax);
        $logger->info('Tax: ' . $tax);
        $logger->info('Total Earning Points: ' . $totalPointEarningAmount);
        $logger->info('Point Earned Multiply By: ' . $this->helper->getPointEarnedMultiplyBy());
        $logger->info('Point Grant Rate: ' . $pointGrantRate);
        $logger->info('Acquired Points: ' . $acquired_points);

        if ($acquired_points < 0) {
            $acquired_points = 0;
        }
        //echo 'Config Provider: '.$acquired_points;
        $quote->setAcquiredPoint($acquired_points)->save();
        $logger->info('================Acquired Point Config Provider End==================');
        //echo $acquired_points;
        return $acquired_points;
    }

    public function calcAcquiredPointsForRealStore($purchaseId, $memberId)
    {
        $acquired_points = 0;
        $getPointRateResult = $this->getPointGrantRate($memberId);
        $pointGrantRate = $getPointRateResult['rate'];
        $oddValue = $getPointRateResult['odd'];
        $order = $this->shopReceipt->loadByPurchaseId($purchaseId);
        $items = $order->getItems();

        $subTotalInclTax = 0;
        $discountedAmount = 0;

        foreach ($items as $item) {
            $subTotalInclTax += ($item->getSubtotalAmount() + $item->getTaxAmount());
            $discountedAmount += $item->getDiscountAmount();
        }
        $tax = (int)(($subTotalInclTax - $discountedAmount) / (1 + (self::TAX_RATE/100)) * (self::TAX_RATE/100));
        //$discountedTotalAmountExcludeTax = $subTotalInclTax - $discountedAmount - $tax;

        $acquired_points = (int)(($order->getTotalAmount() * $this->helper->getPointEarnedMultiplyBy()) * $pointGrantRate);

        if ($order->getAcquiredPoint() <= 0) {
            $order->setAcquiredPoint($acquired_points);
            $order->save();
        }

        return $acquired_points;
    }

    public function getCurrentPoints()
    {
        $result = $this->cpssApiRequest->getMemberStatus($this->customerSession->getMemberId());
        if (isset($result['X-CPSS-Result']) && $result['X-CPSS-Result'] == '000-000-000') {
            $result = json_decode($result['Body'][0][0], true);

            return $result['result']['balance'];
        }

        return 0;
    }

    public function getPointGrantRate($memberId)
    {
        if ($this->pointRate) {
            return $this->pointRate;
        }

        if ($this->customerSession->isLoggedIn() || $this->http->getFrontName() == "rest") {
            $this->pointRate = $this->cpssApiRequest->getPointRate($memberId);
        }

        return $this->pointRate;
    }
}

