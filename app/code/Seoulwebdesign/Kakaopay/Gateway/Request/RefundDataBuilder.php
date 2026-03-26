<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Seoulwebdesign\Kakaopay\Gateway\Request;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Gateway\Helper;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Seoulwebdesign\Base\Helper\Currency;
use Seoulwebdesign\Kakaopay\Helper\ConfigHelper;
use Seoulwebdesign\Kakaopay\Helper\Constant;

class RefundDataBuilder implements BuilderInterface
{
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var Currency
     */
    protected $currencyHelper;
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param ConfigHelper $configHelper
     * @param TimezoneInterface $timezone
     * @param Currency $currencyHelper
     */
    public function __construct(
        ConfigHelper $configHelper,
        TimezoneInterface $timezone,
        Currency $currencyHelper
    ) {
        $this->configHelper = $configHelper;
        $this->timezone = $timezone;
        $this->currencyHelper = $currencyHelper;
    }

    /**
     * @param $price
     * @return string
     */
    protected function formatPrice($price)
    {
        return sprintf('%.2F', $price);
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = Helper\SubjectReader::readPayment($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $currencyCode = $payment->getOrder()->getOrderCurrencyCode();

        $amount = null;
        try {
            $amount = $this->formatPrice(Helper\SubjectReader::readAmount($buildSubject));
        } catch (\InvalidArgumentException $e) {
            // pass
        }

        //$amount = $amount*$payment->getOrder()->getBaseToOrderRate();

        $orderGrandTotal = $payment->getOrder()->getBaseGrandTotal();
        $totalRefunded  = $payment->getOrder()->getBaseTotalOnlineRefunded();

        $canRefundAmount = $orderGrandTotal - $totalRefunded;
        $isPartial = $canRefundAmount < $orderGrandTotal;

        $amount = $this->currencyHelper->convert($amount, $currencyCode, 'KRW');

        $tid = $payment->getAdditionalInformation(Constant::KAKAOPAY_RESPONSE_TID);
        $data =
            [
                'cid' => strval($this->configHelper->getCID()),
                'tid' => $tid,
                'cancel_amount' => $amount,
                'cancel_tax_free_amount' => 0,
                'cancel_vat_amount' => 0
            ];
        return $data;
    }
}
