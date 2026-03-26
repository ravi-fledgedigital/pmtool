<?php
/**
 * phpcs:ignoreFile
 * 
 * Copyright © CO-WELL ASIA CO.,LTD.
 * See COPYING.txt for license details.
 */
/** phpcs:ignoreFile */
namespace OnitsukaTigerIndo\Checkout\Block\Cart;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

/**
 * Class LayoutProcessor
 * OnitsukaTigerIndo\Checkout\Block\Cart
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @param  array $jsLayout
     * @return array
     */
    public function process($jsLayout): array
    {
        $jsLayout = $this->disableFieldDistrict($jsLayout);
        return $jsLayout;
    }

    /**
     * @param $jsLayout
     * @param $fieldCity
     * @param $fieldDistrict
     * @return mixed
     */
    public function setShippingToJsLayout($jsLayout, $fieldCity, $fieldDistrict)
    {
        $fields = $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];

        $fields['telephone']['label'] = __('Phone Number');
        $fields['telephone']['validation'] = array_merge($fields['telephone']['validation'], ['max_text_length' => 15]);
        $fields['telephone']['validation'] = array_merge($fields['telephone']['validation'], ['min_text_length' => 7]);
        $fields['street']['children'][0]['label'] = __('Street Address');
        $fields['street']['children'][0]['validation'] = array_merge($fields['street']['children'][0]['validation'], ['max_text_length' => 100]);
        $fields['street']['children'][1]['visible'] = false;
        $fields['firstname']['validation'] = array_merge($fields['firstname']['validation'], ['max_text_length' => 15]);
        $fields['lastname']['validation'] = array_merge($fields['firstname']['validation'], ['max_text_length' => 15]);
        $fields['telephone']['validation'] = array_merge($fields['telephone']['validation'], ['validate-input-space' => true]);
        $fields['city'] = $fieldCity;
        $fields['city']['config']['customScope'] = 'shippingAddress';
        $fields['city']['dataScope'] = 'shippingAddress.city';
        $fields['district'] = $fieldDistrict;
        $fields['district']['config']['customScope'] = 'shippingAddress.custom_attributes';
        $fields['district']['dataScope'] = 'shippingAddress.custom_attributes.district';
        $fields['district']['filterBy']['target'] = 'checkoutProvider:shippingAddress.city';

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'] = $fields;
        return $jsLayout;
    }

    /**
     * @param $jsLayout
     * @param $fieldCity
     * @param $fieldDistrict
     * @return mixed
     */
    public function setBillingToJsLayout($jsLayout, $fieldCity, $fieldDistrict)
    {
        $paymentMethods = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'];

        foreach ($paymentMethods as $paymentGroup => $method) {
            if (isset($method['component']) and $method['component'] === 'Magento_Checkout/js/view/billing-address') {
                $fields = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children'];

                $fields['telephone']['label'] = __('Phone Number');
                $fields['telephone']['validation'] = array_merge($fields['telephone']['validation'], ['max_text_length' => 15]);
                $fields['telephone']['validation'] = array_merge($fields['telephone']['validation'], ['min_text_length' => 7]);
                $fields['street']['sortOrder'] = 35;
                $fields['street']['children'][0]['label'] = __('Street Address');
                $fields['street']['children'][0]['validation'] = array_merge($fields['street']['children'][0]['validation'], ['max_text_length' => 100]);
                $fields['street']['children'][1]['visible'] = false;
                $fields['postcode']['sortOrder'] = 105;
                $fields['firstname']['validation'] = array_merge($fields['firstname']['validation'], ['max_text_length' => 15]);
                $fields['lastname']['validation'] = array_merge($fields['firstname']['validation'], ['max_text_length' => 15]);
                $fields['telephone']['validation'] = array_merge($fields['telephone']['validation'], ['validate-input-space' => true]);
                $fields['city'] = $fieldCity;
                $fields['city']['config']['customScope'] = $method['dataScopePrefix'];
                $fields['city']['dataScope'] = $method['dataScopePrefix'] . '.city';
                $fields['district'] = $fieldDistrict;
                $fields['district']['config']['customScope'] = $method['dataScopePrefix'] . '.custom_attributes';
                $fields['district']['dataScope'] = $method['dataScopePrefix'] . '.custom_attributes.district';
                $fields['district']['filterBy']['target'] = '${ $.provider }:' . $method['dataScopePrefix'] . '.city';

                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children'] = $fields;
            }
        }

        return $jsLayout;
    }

    /**
     * @param $jsLayout
     * @return array
     */
    public function disableFieldDistrict($jsLayout)
    {
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']
        ['shipping-address-fieldset']['children']['district']['config']['componentDisabled'] = true;

        $configuration = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'];
        foreach ($configuration as $paymentGroup => $groupConfig) {
            if (isset($groupConfig['component']) and $groupConfig['component'] === 'Magento_Checkout/js/view/billing-address') {
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['district']['config']['componentDisabled'] = true;
            }
        }

        return $jsLayout;
    }
}
