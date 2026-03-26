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
use Seoulwebdesign\Base\Helper\Data as BaseHelper;
use Seoulwebdesign\Kakaopay\Helper\Constant;

/**
 * Class RefundDataBuilder
 */
class InitializeDataBuilder implements BuilderInterface
{

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Seoulwebdesign\Tossopenpay\Helper\Currency
     */
    protected $currencyHelper;
    /**
     * @var BaseHelper
     */
    protected $baseHelper;

    /**
     * PaymentDataBuilder constructor.
     * @param ConfigHelper $configHelper
     * @param BaseHelper $baseHelper
     * @param TimezoneInterface $timezone
     * @param Currency $currencyHelper
     */
    public function __construct(
        ConfigHelper $configHelper,
        BaseHelper $baseHelper,
        TimezoneInterface $timezone,
        Currency $currencyHelper
    ) {
        $this->configHelper = $configHelper;
        $this->baseHelper = $baseHelper;
        $this->timezone = $timezone;
        $this->currencyHelper = $currencyHelper;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = Helper\SubjectReader::readPayment($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $currencyCode = $payment->getOrder()->getOrderCurrencyCode();
        $order = $payment->getOrder();

//        $amount = null;
//        try {
//            $amount = $this->formatPrice(Helper\SubjectReader::readAmount($buildSubject));
//        } catch (\InvalidArgumentException $e) {
//            // pass
//        }

        $amount = $order->getBaseGrandTotal();
        $amount = $this->currencyHelper->convert($amount, $order->getBaseCurrencyCode(), 'KRW');

        $approvalUrl = $this->baseHelper->getUrl(Constant::KAKAOPAY_APPROVAL_URL, ['oid'=>$order->getIncrementId()]);
        $failUrl = $this->baseHelper->getUrl(Constant::KAKAOPAY_FAIL_URL, ['oid'=>$order->getIncrementId()]);
        $cancelUrl = $this->baseHelper->getUrl(Constant::KAKAOPAY_CANCEL_URL, ['oid'=>$order->getIncrementId()]);

        $avaiCardsJson = $this->configHelper->getAvailableCards();
        $avaiCards = json_decode($avaiCardsJson);

        $data =
            [
                'cid' => strval($this->configHelper->getCID()),
                'partner_order_id' => $order->getIncrementId(),
                'partner_user_id' => $order->getCustomerEmail(),
                'item_name' => $this->getProductName($order),
                'item_code' => $order->getIncrementId(),
                'quantity' => 1,//intval($order->getTotalQtyOrdered()),
                'total_amount' => $amount,
                'tax_free_amount' => 0,
                'vat_amount' => 0,
                'approval_url' => $approvalUrl,
                'cancel_url' => $cancelUrl,
                'fail_url' => $failUrl,
                'available_cards' => $this->configHelper->getAvailableCards(),
                'install_month' => '',
                'custom_json' => '',
                'user_phone_number' => ''
            ];
        if ($avaiCards) {
            $data['available_cards'] = $this->configHelper->getAvailableCards();
        }
        if ($this->configHelper->getPaymentMethodType()) {
            $data['payment_method_type'] = $this->configHelper->getPaymentMethodType();
        }
        return $data;
    }

    /**
     * create product name string
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getProductName($order)
    {
        $name = [];
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            /** @var $item \Magento\Sales\Model\Order\Item */
            $productName = $this->baseHelper->convertEncodeToUtf8($item->getProduct()->getName());
            $name[] = $productName . "-" . $item->getQtyOrdered();
        }
        $fullName =  implode('|', $name);
        return mb_strcut($fullName, 0, 99);
    }
}
