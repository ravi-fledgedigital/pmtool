<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerKorea\Checkout\Plugin\Block\Checkout;

use Magento\Customer\Model\Session;
use OnitsukaTigerKorea\Checkout\Helper\Data;

/**
 * Class LayoutProcessor
 * @package OnitsukaTigerKorea\Checkout\Plugin\Block\Checkout
 */
class LayoutProcessor
{
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * LayoutProcessor constructor.
     * @param Data $dataHelper
     */
    public function __construct(
        Data $dataHelper,
        Session $customerSession
    )
    {
        $this->dataHelper = $dataHelper;
        $this->customerSession = $customerSession;
    }
    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, array $jsLayout)
    {
        if ($this->dataHelper->isCheckoutEnabled()) {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0]['config']['elementTmpl'] = 'OnitsukaTigerKorea_Checkout/form/element/readonly-field';
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['postcode']['component'] = 'OnitsukaTigerKorea_Checkout/js/form/element/readonly-field';
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['country_id']['value'] = 'KR';
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['city']['validation'] = [];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id']['visible'] = false;
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id']['value'] = 0;
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id']['config']['componentDisabled'] = true;
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['lastname']['visible'] = false;
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['lastname']['validation'] = [];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['firstname']['validation'] = ['required-entry' => true, 'xml-hangul-validate' => true,'max_text_length'=>32];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0]['validation'] = ['required-entry' => true, 'max_text_length' => 150];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][1]['validation'] = ['max_text_length' => 150];
            $configuration = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'];

            foreach ($configuration as $paymentGroup => $groupConfig) {
                if (isset($groupConfig['component']) AND $groupConfig['component'] === 'Magento_Checkout/js/view/billing-address') {
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][0]['config']['elementTmpl'] = 'OnitsukaTigerKorea_Checkout/form/element/readonly-field';
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['postcode']['component'] = 'OnitsukaTigerKorea_Checkout/js/form/element/readonly-field';
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['country_id']['value'] = 'KR';
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['city']['validation'] = [];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region_id']['visible'] = false;
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region_id']['value'] = 0;
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region_id']['config']['componentDisabled'] = true;
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['lastname']['visible'] = false;
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['lastname']['validation'] = [];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['firstname']['validation'] = ['required-entry' => true, 'xml-hangul-validate' => true, 'max_text_length'=>32];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][0]['validation'] = ['required-entry' => true, 'max_text_length' => 150];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][1]['validation'] = ['max_text_length' => 150];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['telephone']['config']['tooltip'] = false;
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['sortOrder'] = 60;
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['country_id']['sortOrder'] = 70;
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['city']['sortOrder'] = 80;
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['postcode']['sortOrder'] = 90;
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region_id']['sortOrder'] = 100;
                }
            }

        }

        if($this->dataHelper->getStoreCode() == 'web_kr_ko' && !$this->customerSession->isLoggedIn()) {
            //add full agreement checkbox
            $paymentChildren = &$jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children'];
            $paymentChildren['payments-list']['children']['before-place-order']['children']['fullagreement'] = [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => [
                    'customScope' => 'billingAddress',
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'OnitsukaTigerKorea_Checkout/checkout/select-all',
                    'id' => 'fullagreement'
                ],
                'dataScope' => 'checkoutAgreements',
                'description' => __('전체 동의'),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'validation' => ['required-entry' => false],
                'sortOrder' => 90,
                'id' => 'fullagreement',
                'checkbox_id' => 'full_agreement',
            ];

            //add 2nd terms and condition agreement checkbox
            $paymentChildren = &$jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children'];
            $paymentChildren['payments-list']['children']['before-place-order']['children']['secoundtermsandconditionagreement'] = [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => [
                    'customScope' => 'billingAddress',
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'OnitsukaTigerKorea_Checkout/checkout/secound-terms-and-condition-agreement',
                    'id' => 'secoundtermsandconditionagreement'
                ],
                'dataScope' => 'checkoutAgreements',
                'description' => __('(선택) 광고성 정보의 수신에 동의합니다 .'),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'validation' => ['required-entry' => false],
                'sortOrder' => 130,
                'id' => 'secoundtermsandconditionagreement',
                'checkbox_id' => 'secound_terms_and_condition_agreement_' . rand(10, 100),
            ];
            $paymentChildren['additional-payment-validators']['children']['terms-and-condition-agreement-validator']
            ['component'] = 'OnitsukaTigerKorea_Checkout/js/view/secound-terms-and-condition-agreement-validation';
        }


        return $jsLayout;
    }
}