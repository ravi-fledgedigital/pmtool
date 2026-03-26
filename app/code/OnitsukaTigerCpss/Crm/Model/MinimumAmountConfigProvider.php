<?php

namespace OnitsukaTigerCpss\Crm\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use OnitsukaTigerCpss\Crm\Helper\HelperData;

class MinimumAmountConfigProvider implements ConfigProviderInterface
{
    /**
     * @var HelperData
     */
    protected HelperData $helper;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layout;

    /**
     * @param HelperData $helper
     * @param \Magento\Framework\View\LayoutInterface $layout
     */
    public function __construct(
        HelperData $helper,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->helper = $helper;
        $this->layout = $layout;
    }

    public function getConfig()
    {
        $config = [];

        if ($this->helper->isEnableModule()) {
            $config['minimum_oder_amount_world_pay_payment_method'] = (!empty($this->helper->getMinimumOrderAmountForWorldPayPaymentMethod())) ? $this->helper->getMinimumOrderAmountForWorldPayPaymentMethod() : 0;
            $config['minimum_oder_amount_razer_payment_method'] = (!empty($this->helper->getMinimumOrderAmountRazerPaymentMethod())) ? $this->helper->getMinimumOrderAmountRazerPaymentMethod() : 2;
            $config['minimum_oder_amount_omise_payment_method'] = (!empty($this->helper->getMinimumOrderAmountForOmisePaymentMethod())) ? $this->helper->getMinimumOrderAmountForOmisePaymentMethod() : 20;
            $config['minimum_oder_amount_adyen_kakao_pay_payment_method'] = (!empty($this->helper->getMinimumOrderAmountForAdyenKakaoPayPaymentMethod())) ? $this->helper->getMinimumOrderAmountForAdyenKakaoPayPaymentMethod() : 10;
            $config['customer_membership_agreement_guidance'] = $this->layout->createBlock('Magento\Cms\Block\Block')->setBlockId('customer-membership-agreement-guidance')->toHtml();
        }

        return $config;
    }
}
