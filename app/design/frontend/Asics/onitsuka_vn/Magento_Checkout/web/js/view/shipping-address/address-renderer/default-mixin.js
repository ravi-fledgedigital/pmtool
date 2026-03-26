/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Customer/js/model/customer',
    'Magento_Ui/js/modal/alert',
    'Magento_Customer/js/customer-data',
    'ko',
    'Magento_Customer/js/model/customer-addresses',
    'Magento_Customer/js/model/address-list',
    'Magento_Customer/js/model/customer/address',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'mage/translate',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/address-converter'
], function ($,
             customer,
             alert ,
             customerData,
             ko,
             defaultProvider,
             addressList,
             Address,
             formPopUpState,
             $t,
             selectShippingAddressAction,
             checkoutData,
             addressConverter) {
    'use strict';

    var mixin = {
        addressId : null,
        reloadComponent : true,
        deleteUrlPrefix : null,
        getCustomerEmail: function () {
            return customer.customerData.email;
        },
        getAddressType: function (address) {
            $('.opc-wrapper .shipping-address-item:first-child').addClass('selected-item');
            if($('.opc-wrapper .shipping-address-item.selected-item').length > 1){
                $('.opc-wrapper .shipping-address-item:first-child').removeClass('selected-item');
            }
            $('.checkout-shipping-address > .step-title').text($t('Saved addresses'));
            return $t('home');
        },
        getAddressLine : function (street) {
            var htmlAddress = ''
            for (var i=0;i<street.length;i++){
                htmlAddress += '<span>' +  street[i]+'</span>,</br>';
            }
            return htmlAddress;
        },
        getAddressPhone: function (phone) {
            if(typeof(window.telephonePrefix) !='undefined') {
                return window.telephonePrefix + '-' +phone.substring(2);
            }
            return phone;
        },
        getAddressRegion: function (region) {
            region = region ? region : '';
            if(typeof(region) =='object') {
                return region.region;
            }
            return region;
        },
        selectedAddress:function () {
            $('.shipping-address-item').removeClass('selected-item not-selected-item');
            if(typeof(event) !='undefined') {
                $(event.currentTarget).addClass('selected-item');
                if($(event.currentTarget).hasClass('shipping-address-item')) {
                    var addressShipping = this.address();
                    selectShippingAddressAction(addressShipping);
                    checkoutData.setSelectedShippingAddress(this.address().getKey());
                }
            }
        },
        selectAddress: function () {
            var addressShipping = this.address();
            selectShippingAddressAction(addressShipping);
            checkoutData.setSelectedShippingAddress(this.address().getKey());
            checkoutData.setSelectedBillingAddress(this.address().getKey());
            $('#shipping-method-buttons-container button').trigger('click');
        },
        /**
         * Edit address.
         */
        editAddress: function () {
            formPopUpState.isVisible(true);
            if($(".form-address-new-popup.modal-content").length) {
                if(!$('.form-address-new-popup.modal-content').find('#opc-new-shipping-address').length) {
                    $("#opc-new-shipping-address").appendTo($(".form-address-new-popup.modal-content"));
                }
            }
            this.showPopup();
            this._bindAddress();
            $('.modal-popup .form-shipping-address').removeClass('hidden');
        },
        /**
         *
         * @param id
         * @private
         */
        _bindAddress: function () {
            var self = this;
            $('#opc-new-shipping-address').find('[name="validations-address-character"]').val('').trigger('change');
            $.each(self.address(), function( index, address ) {
                $('#opc-new-shipping-address').find('[id="'+index+'"]').val(address).trigger('change');
                $('#opc-new-shipping-address').find('[name="'+index+'"]').val(address).trigger('change');
            });
            $.each(self.address().street, function( index, address ) {
                if(address) {
                    $('#opc-new-shipping-address').find('[name="street['+index+']"]').val($.parseHTML(address)[0]['textContent']).trigger('change');
                }
            });
            self._bindTelephone();
            $('#opc-new-shipping-address').find('[name="country_id"]').val(self.address().countryId).trigger('change');
            $('#opc-new-shipping-address').find('#id').val(self.address().customerAddressId);
            $('#opc-new-shipping-address').find('[name="region_id"]').val(self.address().regionId).trigger('change');
            $('#opc-new-shipping-address').find('[name="city"]').val(self.address().city).trigger('change');
            $('#opc-new-shipping-address').find('div.field-error').hide();
            $('#opc-new-shipping-address').find('[name*="validations-address-character"] div.field-error').show();
        },
        /**
         *
         * @private
         */
        _bindTelephone: function(){
            var self = this;
            var telephoneNumber = self.address().telephone.substring(2);
            $('#opc-new-shipping-address').find('#ShipMobile').val(telephoneNumber);
        },
        _clearFormData: function (){
            $('#opc-new-shipping-address').find('[name="validations-address-character"]').val('').trigger('change');
            $('.form-shipping-address').find('input[type="text"]:not(".mobile-country-code")').val('');
            $('.form-shipping-address').find('input#id').val('');
            $('.form-shipping-address').find('input#ShipMobile').val('');
            $('.form-shipping-address').find('select').val('');
            $('.form-shipping-address').find('[name="country_id"]').val(window.defaultCountry).trigger('change');
            $('.form-shipping-address').find('[name="region_id"]').val('').trigger('change');
            $('.form-shipping-address').find('#region').hide();
            $('.form-shipping-address').find('[name="region"]').val('Vietnam').trigger('change');
            $('.form-shipping-address').find('[name="city"]').val('Vietnam').trigger('change');
        },
        deleteAddress: function (addressId) {
            var self = this;
            self.addressId = addressId;
            self.deleteUrlPrefix = $('#checkout').data('address');
            $('.opc-wrapper .required_fields_txt,.opc-wrapper .checkout-shipping-address .actions-toolbar,.form-shipping-address').addClass('hidden');
            if(typeof(event) !='undefined'){
                if($(event.currentTarget).hasClass('delete-address-link')){
                    alert({
                        content: $.mage.__('Do you want to remove the address?'),
                        modalClass: 'delete-address-alert',
                        buttons: [{
                            text: $.mage.__('Yes'),
                            class: 'action primary accept',
                            click: function () {
                                this.closeModal(true);
                                if(self.deleteUrlPrefix){
                                    $('body > .loading-mask').show();
                                    $.ajax({
                                        url: self.deleteUrlPrefix,
                                        data: {
                                            'id': self.addressId,
                                            'isAjax': 1,
                                            'form_key': $('.columns .column.main input[name="form_key"]').val()
                                        },
                                        type: 'post',
                                        dataType: 'json',
                                        cache: false,
                                        success: function (res) {
                                            if(res.success){
                                                window.customerData = res.customerData;
                                                $('#customer_address_'+self.addressId).addClass('hidden');
                                                $.cookieStorage.set('mage-messages', '');
                                                $('.checkout-shipping-address > .step-title').text($t('Saved addresses'));
                                                $.each($('.shipping-address-item:not(.hidden)'), function (key, item) {
                                                    if(parseInt(key) == 0) {
                                                        $(item).addClass('selected-item');
                                                    }
                                                });
                                                if($('.shipping-address-item:not(.hidden)').length){
                                                    $.each(window.customerData.addresses, function (key, item) {
                                                        var addressData = new Address(item);
                                                        selectShippingAddressAction(addressData);
                                                        checkoutData.setSelectedShippingAddress(addressData.getKey());
                                                        return false;
                                                    });
                                                } else {
                                                    if(!window.customerData.addresses.length) {
                                                        var address = addressConverter.formAddressDataToQuoteAddress(
                                                            checkoutData.getShippingAddressFromData()
                                                        );
                                                        selectShippingAddressAction(address);
                                                    }
                                                    self._clearFormData();
                                                    $('.opc-new-shipping-address,.required_fields_txt,.actions-toolbar').show();
                                                    $('.new-address-popup').hide();
                                                    $('.checkout-shipping-address > .step-title').text($t('Shipping Address'));
                                                    $('.opc-wrapper .required_fields_txt,.opc-wrapper .checkout-shipping-address .actions-toolbar,.form-shipping-address').removeClass('hidden');
                                                    $("#opc-new-shipping-address").parent().addClass('form-address-new-popup');
                                                    $("#opc-new-shipping-address").appendTo($(".add-new-address-form-popup"));
                                                    $(".add-new-address-form-popup").show();
                                                }
                                            }
                                            $('body > .loading-mask').hide();
                                        }
                                    });
                                }
                            }
                        }, {
                            text: $.mage.__('No'),
                            class: 'action cancel',
                            click: function () {
                                this.closeModal(true);
                            }
                        }]
                    });
                }
            }
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
