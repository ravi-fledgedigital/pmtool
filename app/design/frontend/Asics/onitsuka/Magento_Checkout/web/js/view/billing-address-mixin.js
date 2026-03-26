/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
        'jquery',
        'ko',
        'underscore',
        'Magento_Ui/js/form/form',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-billing-address',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Magento_Checkout/js/model/billing-address-postcode-validator'
    ],
    function (
        $,
        ko,
        _,
        Component,
        customer,
        addressList,
        quote,
        createBillingAddress,
        selectBillingAddress,
        checkoutData,
        checkoutDataResolver,
        customerData,
        setBillingAddressAction,
        globalMessageList,
        $t,
        billingAddressPostcodeValidator
    ) {
        'use strict';

        var lastSelectedBillingAddress = null,
            countryData = customerData.get('directory-data'),
            addressOptions = addressList().filter(function (address) {
                return address.getType() === 'customer-address';
            });

        var mixin = {
            /**
             * Update address action
             */
            updateAddress: function () {
                var addressData, newBillingAddress;
                var checkErrorCharaterAddress = $(".billing-address-form input[name='validations-address-character']").parent().find('.field-error');
                if($(".billing-address-form input[name='validations-address-character']").length > 0 && checkErrorCharaterAddress.length > 0) {
                    return false;
                }

                if (this.selectedAddress() && !this.isAddressFormVisible()) {
                    selectBillingAddress(this.selectedAddress());
                    checkoutData.setSelectedBillingAddress(this.selectedAddress().getKey());
                } else {
                    this.source.set('params.invalid', false);
                    this.source.trigger(this.dataScopePrefix + '.data.validate');

                    if (this.source.get(this.dataScopePrefix + '.custom_attributes')) {
                        this.source.trigger(this.dataScopePrefix + '.custom_attributes.data.validate');
                    }

                    if (!this.source.get('params.invalid')) {
                        addressData = this.source.get(this.dataScopePrefix);

                        if (customer.isLoggedIn() && !this.customerHasAddresses) { //eslint-disable-line max-depth
                            this.saveInAddressBook(1);
                        }
                        addressData['save_in_address_book'] = this.saveInAddressBook() ? 1 : 0;
                        newBillingAddress = createBillingAddress(addressData);
                        // New address must be selected as a billing address
                        selectBillingAddress(newBillingAddress);
                        checkoutData.setSelectedBillingAddress(newBillingAddress.getKey());
                        checkoutData.setNewCustomerBillingAddress(addressData);
                    }
                }
                this.updateAddresses();
                if (this.selectedAddress() && !this.isAddressFormVisible()) {

                }else{
                    if (!this.source.get('params.invalid')) {
                        $('.checkout-payment-method .payment-method._active .payment-method-content .action.primary.checkout').trigger('click');
                    }
                }
            },
            /**
             * @return {Boolean}
             */
            useShippingAddress: function () {
                $('.checkout-billing-address').find('[name="validations-address-character"]').val('').trigger('change');
                if (this.isAddressSameAsShipping()) {
                    selectBillingAddress(quote.shippingAddress());

                    this.updateAddresses();
                    this.isAddressDetailsVisible(true);
                } else {
                    lastSelectedBillingAddress = quote.billingAddress();
                    quote.billingAddress(null);
                    this.isAddressDetailsVisible(false);
                    $(".checkout-payment-method .payment-method._active .payment-method-content .field-select-billing select").each(function () {
                        $(this).find("option[value='']").prop('selected', true).trigger('change');
                    });
                    $('.checkout-payment-method .payment-method._active  .actions-toolbar div.primary .primary').removeClass('disabled');
                    $('.checkout-payment-method .payment-method._active  .actions-toolbar div.primary .primary').removeAttr('disabled');
                    var valueAddress = [];
                    $(".checkout-payment-method .payment-method._active .payment-method-content").find('input[name*="street"]').each(function () {
                        if($(this).val()){
                            valueAddress.push($(this).val());
                        }
                    });
                    $(this).parents('form').find("[name='postcode']").prop('readonly', false);
                    if($('.payment-method._active fieldset[data-form="billing-new-address"] select[name="country_id"]').val() == 'MY') {
                        if ($('.payment-method._active fieldset[data-form="billing-new-address"] select[name="region_id"]').find('option:selected').text() == "Wilayah Persekutuan Labuan") {
                            $('.payment-method._active fieldset[data-form="billing-new-address"] select[name="region_id"]').parents('form').find("[name='postcode']").prop('readonly', true);
                            $('.payment-method._active fieldset[data-form="billing-new-address"] select[name="region_id"]').parents('form').find("[name='postcode']").val("87000").trigger('change');
                        }
                    }
                    $(".checkout-payment-method .payment-method._active .payment-method-content").find('[name="validations-address-character"]').val(valueAddress.join(' ').trim()).trigger('change');
                }
                checkoutData.setSelectedBillingAddress(null);
                return true;
            },
            getShipping :function (checked) {
                if(checked._latestValue) {
                    $('.checkout-billing-address .billing-address-same-as-shipping-block input[type="checkbox"]').prop('checked',true);
                }
                return checked;
            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    });
