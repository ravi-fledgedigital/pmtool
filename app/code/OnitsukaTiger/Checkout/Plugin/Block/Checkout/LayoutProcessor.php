<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Checkout\Plugin\Block\Checkout;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\Theme\ThemeProvider;

/**
 * Class LayoutProcessor
 * @package OnitsukaTiger\Customer\Plugin\Block\Checkout
 */
class LayoutProcessor
{
    const LENGTH_OF_POSTCODE = 'customer/address/length_of_postcode';
    const SHOW_TELEPHONE_PREFIX = 'general/telephone_prefix/enable';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ThemeProvider
     */
    private $themeProvider;

    /**
     * LayoutProcessor constructor.
     * @param StoreManagerInterface|null $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager = null,
        ThemeProvider $themeProvider,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()
            ->get(StoreManagerInterface::class);
        $this->scopeConfig = $scopeConfig;
        $this->themeProvider = $themeProvider;
    }
    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, array  $jsLayout)
    {

        $storeId = $this->storeManager->getStore()->getId();
        $textLenght = $this->getConfigValue(self::LENGTH_OF_POSTCODE, $storeId);
        $showTelephonePrefix = $this->getConfigValue(self::SHOW_TELEPHONE_PREFIX, $storeId);
        $countryId = $this->getConfigValue('general/country/default', $storeId);
        $addressValidations = $this->getConfigValue('customer_address_validate/general/enable_limitations', $storeId);
        $addressValidationsMaxlength = $this->getConfigValue('customer_address_validate/general/limitations_character', $storeId) ? $this->getConfigValue('customer_address_validate/general/limitations_character', $storeId) : '81';
        $addressValidationsCharacter = $this->getConfigValue('customer_address_validate/general/enable_validation_character', $storeId) ? $this->getConfigValue('customer_address_validate/general/enable_validation_character', $storeId) : 0;
        $postcodeValidation = [
            'required-entry' => true,
            'validate-number' => true,
            'validate-digits' => true,
            'min_text_length' => $textLenght,
            'max_text_length' => $textLenght,
        ];
        if (in_array($storeId, ['6', '7'])) {
            $phoneValidation = [
                'required-entry' => true,
                'validate-number' => true,
                'min_text_length' => '7',
                'max_text_length' => '15',

            ];
        } else {
            $phoneValidation = [
                'required-entry' => true,
                'validate-number' => true,
                'min_text_length' => '8',
                'max_text_length' => '12',

            ];
        }
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['country_id']['caption'] =__('-- Select Country --');
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['country_id']['value'] = $countryId;
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['label'] = __('Mobile No.');
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['config']['tooltip'] = false;
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['validation'] = $phoneValidation;
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['sortOrder'] = 30;
        if($showTelephonePrefix){
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['component'] = 'OnitsukaTiger_Checkout/js/form/element/telephone';
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['config']['elementTmpl'] = 'OnitsukaTiger_Checkout/form/element/telephone';
        }
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['postcode']['label'] = __('Postal Code');
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id']['label'] = __('Province');
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['label'] = __('Address Line 1');
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['city']['validation'] = ['required-entry' => true, 'xml-characters-validate' => true];
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['firstname']['validation'] = ['required-entry' => true, 'xml-characters-validate' => true];
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['lastname']['validation'] = ['required-entry' => true, 'xml-characters-validate' => true];
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id']['validation'] = ['required-entry' => true];
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id']['value'] = 654;
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0]['label'] = __('Address Line 1');
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0]['validation'] = ['required-entry' => true, 'xml-characters-validate' => true, 'max_text_length' => 150];
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][1]['label'] = __('Address Line 2');
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][1]['validation'] = ['xml-characters-validate' => true, 'max_text_length' => 150];
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['postcode']['validation'] = $postcodeValidation;
        if($addressValidationsCharacter) {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][1]['validation'] = ['xml-characters-validate' => true, 'max_text_length' => 150,'validate-character-address' => $addressValidationsCharacter];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0]['validation'] = ['required-entry' => true, 'xml-characters-validate' => true, 'max_text_length' => 150,'validate-character-address' => $addressValidationsCharacter];
        }
        $configuration = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'];
        if($this->isSingaporeThemeEnable()){
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id']['validation'] = ['required-entry' => false];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['city']['validation'] = ['required-entry' => false, 'xml-characters-validate' => true];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['city']['value'] = 'Singapore';
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region']['value'] = 'Singapore';
        }
        if($this->isVietnamThemeEnable()){
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id']['validation'] = ['required-entry' => false];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['city']['validation'] = ['required-entry' => false, 'xml-characters-validate' => true];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['city']['value'] = 'Vietnam';
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region']['value'] = 'Vietnam';
        }
        if($addressValidations){
            $customFieldAddressType = [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => [
                    'customScope' => 'shippingAddress.custom_attributes',
                    'customEntry' => null,
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/input'
                ],
                'dataScope' => 'shippingAddress.validations-address-character',
                'label' => __(''),
                'provider' => 'checkoutProvider',
                'sortOrder' => 71,
                'validation' => [
                    'maximum-character-length' => $addressValidationsMaxlength,
                    'validate-character-length' => true,
                ],
                'filterBy' => null,
                'customEntry' => null,
                'visible' => true
            ];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['address_validation'] = $customFieldAddressType;
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0]['component'] = 'Magento_Customer/js/form/element/address';
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][1]['component'] = 'Magento_Customer/js/form/element/address';
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0]['validation'] = ['required-entry' => true, 'xml-characters-validate' => true];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][1]['validation'] = ['xml-characters-validate' => true];
            if($addressValidationsCharacter) {
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0]['validation'] = ['required-entry' => true, 'xml-characters-validate' => true,'validate-character-address' => $addressValidationsCharacter];
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][1]['validation'] = ['xml-characters-validate' => true,'validate-character-address' => $addressValidationsCharacter];
            }
        }
        foreach ($configuration as $paymentGroup => $groupConfig) {
            if (isset($groupConfig['component']) AND $groupConfig['component'] === 'Magento_Checkout/js/view/billing-address') {
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['country_id']['caption'] =__('-- Select Country --');
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['country_id']['value'] = $countryId;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['firstname']['sortOrder'] = 10;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['lastname']['sortOrder'] = 20;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['telephone']['validation'] = $phoneValidation;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['telephone']['sortOrder'] = 30;
                if($showTelephonePrefix){
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['telephone']['component'] = 'OnitsukaTiger_Checkout/js/form/element/telephone';
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['telephone']['config']['elementTmpl'] = 'OnitsukaTiger_Checkout/form/element/telephone';
                }
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['telephone']['config']['tooltip'] = false;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['sortOrder'] = 100;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['postcode']['sortOrder'] = 60;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['postcode']['validation'] = $postcodeValidation;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['country_id']['sortOrder'] = 70;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region_id']['sortOrder'] = 80;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['city']['sortOrder'] = 90;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['telephone']['label'] = __('Mobile No.');
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['postcode']['label'] = __('Postal Code');
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region_id']['label'] = __('Province');
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region_id']['value'] = 654;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['label'] = __('Address Line 1');
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['company']['visible'] = false;
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['city']['validation'] = ['required-entry' => true, 'xml-characters-validate' => true];
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['firstname']['validation'] = ['required-entry' => true, 'xml-characters-validate' => true];
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['lastname']['validation'] = ['required-entry' => true, 'xml-characters-validate' => true];
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region_id']['validation'] = ['required-entry' => true];
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][0]['label'] = __('Address1');
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][0]['validation'] = ['required-entry' => true, 'xml-characters-validate' => true, 'max_text_length' => 150];
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][1]['label'] = __('Address2');
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][1]['validation'] = ['xml-characters-validate' => true, 'max_text_length' => 150];
                if($addressValidationsCharacter) {
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][1]['validation'] = ['xml-characters-validate' => true, 'max_text_length' => 150,'validate-character-address' => $addressValidationsCharacter];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][0]['validation'] = ['required-entry' => true, 'xml-characters-validate' => true, 'max_text_length' => 150,'validate-character-address' => $addressValidationsCharacter];
                }
                if($addressValidations){
                    $customFieldAddressTypeBilling = [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'billingAddress.custom_attributes',
                            'customEntry' => null,
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'dataScope' => 'billingAddress.validations-address-character',
                        'label' => __(''),
                        'provider' => 'checkoutProvider',
                        'sortOrder' => 101,
                        'validation' => [
                            'maximum-character-length' => $addressValidationsMaxlength,
                            'validate-character-length' => true,
                        ],
                        'filterBy' => null,
                        'customEntry' => null,
                        'visible' => true
                    ];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['address_validation'] = $customFieldAddressTypeBilling;
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][0]['component'] = 'Magento_Customer/js/form/element/address';
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][1]['component'] = 'Magento_Customer/js/form/element/address';
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][0]['validation'] = ['required-entry' => true, 'xml-characters-validate' => true];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][1]['validation'] = ['xml-characters-validate' => true];
                    if($addressValidationsCharacter) {
                        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][1]['validation'] = ['xml-characters-validate' => true,'validate-character-address' => $addressValidationsCharacter];
                        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['street']['children'][0]['validation'] = ['required-entry' => true, 'xml-characters-validate' => true, 'validate-character-address' => $addressValidationsCharacter];
                    }
                }
                if($this->isSingaporeThemeEnable()){
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['city']['value'] = 'Singapore';
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region']['value'] = 'Singapore';
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region_id']['validation'] = ['required-entry' => false];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['city']['validation'] = ['required-entry' => false, 'xml-characters-validate' => true];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['postcode']['sortOrder'] = 100;
                }
                if($this->isVietnamThemeEnable()){
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['city']['value'] = 'Vietnam';
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region']['value'] = 'Vietnam';
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['region_id']['validation'] = ['required-entry' => false];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['city']['validation'] = ['required-entry' => false, 'xml-characters-validate' => true];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['postcode']['sortOrder'] = 100;
                }
            }
        }
        return $jsLayout;
    }

    /**
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param null $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isSingaporeThemeEnable($storeId = null)
    {
        $themeId = $this->getThemeIdByConfig($storeId);
        $theme = $this->themeProvider->getThemeById($themeId);
        if ($theme->getCode() == 'Asics/onitsuka_sg') {
            return true;
        }
        return false;
    }

    public function isVietnamThemeEnable($storeId = null)
    {
        $themeId = $this->getThemeIdByConfig($storeId);
        $theme = $this->themeProvider->getThemeById($themeId);

        if ($theme->getCode() == 'Asics/onitsuka_vn') {
            return true;
        }

        return false;
    }

    /**
     * Check if the current theme is 'onitsuka_id'.
     *
     * @return bool
     */
    private function isOnitsukaIdTheme($storeId = null)
    {
        $themeId = $this->getThemeIdByConfig($storeId);
        $theme = $this->themeProvider->getThemeById($themeId);

        if ($theme->getCode() == 'Asics/onitsuka_id') {
            return true;
        }

        return false;
    }

    /**
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getThemeIdByConfig($storeId = null)
    {

        return $this->scopeConfig->getValue(
            DesignInterface::XML_PATH_THEME_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId ?? $this->storeManager->getStore()->getId()
        );
    }
}
