<?php
namespace Seoulwebdesign\Base\Helper;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;


class Currency extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * Currency constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param CurrencyFactory $currencyFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory
    ) {
        $this->storeManager = $storeManager;
        $this->currencyFactory = $currencyFactory;
        parent::__construct($context);
    }

    /**
     * Converts the amount value from one currency to another.
     * If the $currencyCodeFrom is not specified the current currency will be used.
     * If the $currencyCodeTo is not specified the base currency will be used.
     *
     * @param float $amountValue like 13.54
     * @param string|null $currencyCodeFrom like 'USD'
     * @param string|null $currencyCodeTo like 'BYN'
     * @return float
     * @throws \Exception
     */
    public function convert($amountValue, $currencyCodeFrom = null, $currencyCodeTo = null)
    {
        try {
            /**
             * If is not specified the currency code from which we want to convert - use current currency
             */
            if (!$currencyCodeFrom) {
                $currencyCodeFrom = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
            }

            /**
             * If is not specified the currency code to which we want to convert - use base currency
             */
            if (!$currencyCodeTo) {
                $currencyCodeTo = $this->storeManager->getStore()->getBaseCurrency()->getCode();
            }

            /**
             * Do not convert if currency is same
             */
            if ($currencyCodeFrom != $currencyCodeTo) {
                // Get rate
                $rate = $this->currencyFactory->create()->load($currencyCodeFrom)->getAnyRate($currencyCodeTo);
                if ($rate == false) {
                    throw new \Exception('Unable to get convert rate');
                }
                // Get amount in new currency
                $amountValue = $amountValue * $rate;
            }

            if($currencyCodeTo=='KRW') {
                $amountValue = intval($amountValue);
            }

            return $amountValue;
        } catch (\Throwable $throwable) {
            throw new \Exception('Unable to get convert rate');
        }
    }

    /**
     * @param $apiAmount
     * @return float
     */
    public function convertUsdToCent($apiAmount)
    {
        return $apiAmount = round($apiAmount * 100);
    }

    /**
     * @return array
     */
    public function getConfigAllowCurrencies()
    {
        return $this->currencyFactory->create()->getConfigAllowCurrencies();
    }

    /**
     * @param $kcpCurrency
     * @return bool
     */
    protected function _currencyListCheck($kcpCurrency)
    {
        $availableCurrency = $this->getConfigAllowCurrencies();
        $currency          = ($kcpCurrency == "WON") ? "KRW" : $kcpCurrency;
        $check             = in_array($currency, $availableCurrency);
        return $check;
    }

    /**
     * @param Order $order
     * @param $toCurrency
     * @return float|int|null
     * @throws \Exception
     */
    public function getPayAmount(Order $order, $toCurrency)
    {
        $currentCurrencyCode = $order->getOrderCurrencyCode();
        $amount              = $order->getBaseGrandTotal();
        $kcpCurrencyCheck    = $this->_currencyListCheck($toCurrency);

        if (!$kcpCurrencyCheck) {
            throw new \Exception("Currency rate conversion failed, please contact administrator to setup currency rate first.");
        }

        if ($currentCurrencyCode != "KRW" && $toCurrency == "WON") {
            $amount = $this->convert($amount, $currentCurrencyCode, 'KRW');
        } elseif ($currentCurrencyCode != "USD" && $toCurrency == "USD") {
            $amount = $this->convert($amount, $currentCurrencyCode, 'USD');
            $amount *= 100;
        } elseif ($currentCurrencyCode == "USD" && $toCurrency == "USD") {
            $amount *= 100;
        }

        return (int)$amount;
    }
}
