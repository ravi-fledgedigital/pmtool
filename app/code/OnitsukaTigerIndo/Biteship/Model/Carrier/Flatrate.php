<?php
/**
 * phpcs:ignoreFile
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTigerIndo\Biteship\Model\Carrier;

use Magento\OfflineShipping\Model\Carrier\Flatrate\ItemPriceCalculator;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;

/**
 * Flat rate shipping model
 *
 * @api
 * @since 100.0.2
 */
class Flatrate extends \Magento\OfflineShipping\Model\Carrier\Flatrate
{
    /**
     * Constructs a new instance.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig The scope configuration
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory The rate error factory
     * @param \Psr\Log\LoggerInterface $logger The logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory The rate result factory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory The rate method factory
     * @param \Magento\OfflineShipping\Model\Carrier\Flatrate\ItemPriceCalculator $itemPriceCalculator The item price calculator
     * @param \Magento\Checkout\Model\Session $checkoutSession The checkout session
     * @param \Magento\Customer\Model\Session $customerSession The customer session
     * @param \OnitsukaTigerIndo\Biteship\Helper\Data $dataHelper The data helper
     * @param array $data The data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\OfflineShipping\Model\Carrier\Flatrate\ItemPriceCalculator $itemPriceCalculator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \OnitsukaTigerIndo\Biteship\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->itemPriceCalculator = $itemPriceCalculator;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $rateResultFactory, $rateMethodFactory, $itemPriceCalculator);
    }

    /**
     * Collect and get rates
     *
     * @param RateRequest $request
     * @return Result|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $freeBoxes = $this->getFreeBoxesCount($request);
        $this->setFreeBoxes($freeBoxes);

        /** @var Result $result */
        $result = $this->_rateResultFactory->create();

        $shippingPrice = $this->getShippingPrice($request, $freeBoxes);

        if ($shippingPrice !== false) {
            $method = $this->createResultMethod($shippingPrice);
            $result->append($method);
        }

        return $result;
    }

    /**
     * Returns shipping price
     *
     * @param RateRequest $request
     * @param int $freeBoxes
     * @return bool|float
     */
    private function getShippingPrice(RateRequest $request, $freeBoxes)
    {
        $shippingPrice = false;

        $configPrice = $this->getConfigData('price');
        if ($this->getConfigData('type') === 'O') {
            // per order
            $shippingPrice = $this->itemPriceCalculator->getShippingPricePerOrder($request, $configPrice, $freeBoxes);
        } elseif ($this->getConfigData('type') === 'I') {
            // per item
            $shippingPrice = $this->itemPriceCalculator->getShippingPricePerItem($request, $configPrice, $freeBoxes);
        }

        $shippingPrice = $this->getFinalPriceWithHandlingFee($shippingPrice);

        if ($shippingPrice !== false && $request->getPackageQty() == $freeBoxes) {
            $shippingPrice = '0.00';
        }
        return $shippingPrice;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * Get count of free boxes
     *
     * @param RateRequest $request
     * @return int
     */
    private function getFreeBoxesCount(RateRequest $request)
    {
        $freeBoxes = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                $freeShippingMethod = $item->getFreeShippingMethod();

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    $freeBoxes += $this->getFreeBoxesCountFromChildren($item);
                } elseif ($item->getFreeShipping()
                    && ($freeShippingMethod === null || $freeShippingMethod === 'flatrate_flatrate')
                ) {
                    $freeBoxes += $item->getQty();
                }
            }
        }
        return $freeBoxes;
    }

    /**
     * Creates result method
     *
     * @param int|float $shippingPrice
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    private function createResultMethod($shippingPrice)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/Flatrate_' . date('d-m-y') . '.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('createResultMethod');

        $quoteSubtotal = $this->checkoutSession->getQuote()->getTotals()['subtotal']->getValue();

        if (in_array($this->checkoutSession->getQuote()->getStoreId(), ['6', '7']) && $this->dataHelper->isEnableModule() && !$this->customerSession->isLoggedIn() && $quoteSubtotal < $this->dataHelper->getMinimumPriceForCourierRateApi() && $this->checkoutSession->getQuote()->getShippingAddress()->getPostcode()) {

            $logger->info("inside if");
            $logger->info("storeId = " . $this->checkoutSession->getQuote()->getStoreId());
            $logger->info("moduleEnable = " . $this->dataHelper->isEnableModule());
            $logger->info("isLoggedIn = " . $this->customerSession->isLoggedIn());
            $logger->info("quoteSubtotal = " . $quoteSubtotal);
            $logger->info("postalCode = " . $this->checkoutSession->getQuote()->getShippingAddress()->getPostcode());

            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
            $method = $this->_rateMethodFactory->create();

            $postalCodeArray = [
                'origin_postal_code' => $this->dataHelper->getOriginPostalCodeForCourierRateApi(),
                'destination_postal_code' => $this->checkoutSession->getQuote()->getShippingAddress()->getPostcode(),
                'couriers' => 'sap',
                'items' => []
            ];

            $getShippingRateByPostalcode = json_decode(
                $this->dataHelper->getCurlCall(
                    $this->dataHelper->getBiteshipCourierRateApi(),
                    json_encode($postalCodeArray, true)
                ),
                true
            );

            $logger->info("=========getShippingRateByPostalcode=========");
            $logger->info(print_r($getShippingRateByPostalcode, true));

            $finalShippingRate = [];
            if ($getShippingRateByPostalcode['success'] == 1) {
                foreach ($getShippingRateByPostalcode['pricing'] as $key => $shippingRate) {
                    if ($shippingRate['courier_service_name'] == 'Regular Service' && $shippingRate['courier_service_code'] == 'reg') {
                        $finalShippingRate = $shippingRate;
                    }
                }
            }

            $logger->info("=========finalShippingRate=========");
            $logger->info(print_r($finalShippingRate, true));

            $method->setCarrier('flatrate');
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod('flatrate');
            $method->setMethodTitle($this->getConfigData('name'));

            $shippingPrice = (isset($finalShippingRate['price'])) ? ($finalShippingRate['price']) : '';

            $method->setPrice($shippingPrice);
            $method->setCost($shippingPrice);
            return $method;
        } else {
            $logger->info("This order is not from indonesia");
            $method = $this->_rateMethodFactory->create();

            $method->setCarrier('flatrate');
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod('flatrate');
            $method->setMethodTitle($this->getConfigData('name'));

            $method->setPrice($shippingPrice);
            $method->setCost($shippingPrice);
            return $method;
        }
    }

    /**
     * Returns free boxes count of children
     *
     * @param mixed $item
     * @return mixed
     */
    private function getFreeBoxesCountFromChildren($item)
    {
        $freeBoxes = 0;
        foreach ($item->getChildren() as $child) {
            if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                $freeBoxes += $item->getQty() * $child->getQty();
            }
        }
        return $freeBoxes;
    }
}
