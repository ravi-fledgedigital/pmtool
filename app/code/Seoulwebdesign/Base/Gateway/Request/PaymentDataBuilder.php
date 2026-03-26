<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Seoulwebdesign\Base\Gateway\Request;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Gateway\Helper;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Magento\Sales\Model\Order\Payment;
use Seoulwebdesign\Base\Helper\Currency;
use Seoulwebdesign\Base\Helper\Data;

/**
 * Class RefundDataBuilder
 * @package Seoulwebdesign\Base\Gateway\Request
 */
class PaymentDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var Currency
     */
    protected $currencyHelper;

    /**
     * PaymentDataBuilder constructor.
     * @param Data $helper
     * @param TimezoneInterface $timezone
     * @param Currency $currencyHelper
     */
    public function __construct(
        Data $helper,
        TimezoneInterface $timezone,
        Currency $currencyHelper
    ) {
        $this->helper = $helper;
        $this->timezone = $timezone;
        $this->currencyHelper = $currencyHelper;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException|\Exception
     */
    public function build(array $buildSubject)
    {
        $paymentDO = Helper\SubjectReader::readPayment($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $currencyCode = $payment->getOrder()->getOrderCurrencyCode();
        $order = $payment->getOrder();

        $amount = null;
        try {
            $amount = $this->formatPrice(Helper\SubjectReader::readAmount($buildSubject));
        } catch (\InvalidArgumentException $e) {
            // pass
        }

        $amount = $amount*$payment->getOrder()->getBaseToOrderRate();
        $params['orderId'] = $order->getIncrementId();
        $params['orderName'] = $this->getGoodName($order);
        $params['amount'] = $this->currencyHelper->convert($amount, $currencyCode, 'KRW');
        $params['customerEmail'] = $order->getBillingAddress()->getEmail();
        return $params;
    }


    /**
     * @param $order
     * @return string
     */
    protected function getGoodName($order): string
    {
        $items = $order->getAllItems();
        $count = 0;
        $goodsName = "";
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            if ($count == 0) {
                $goodsName = $item->getName();
            }
            if ($item->getProductType() == "simple") {
                $count++;
            }
        }
        $goodsName = ($count > 1) ? $goodsName . ' and ' . ($count-1) . ' other items' : $goodsName;

        return $goodsName;
    }
}
