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
], function ($, customer, alert , customerData, ko, defaultProvider, addressList, Address, formPopUpState, $t) {
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
            if(typeof(region) =='object') {
                return region.region;
            }
            return region;
        },
        selectedAddress:function () {
            $('.shipping-address-item').removeClass('selected-item not-selected-item');
            if(typeof(event) !='undefined') {
                $(event.currentTarget).addClass('selected-item')
            }
        },
        selectAddress: function () {
            this._super();
            $('#shipping-method-buttons-container button').trigger('click');
        },
        /**
         * Edit address.
         */
        editAddress: function () {
            formPopUpState.isVisible(true);
            this.showPopup();
            this._bindAddress();

        },
        /**
         *
         * @param id
         * @private
         */
        _bindAddress: function () {
            var self = this;
            $.each(self.address(), function( index, address ) {
                $('#opc-new-shipping-address').find('[id="'+index+'"]').val(address).trigger('change');
                $('#opc-new-shipping-address').find('[name="'+index+'"]').val(address).trigger('change');
            });
            $.each(self.address().street, function( index, address ) {
                $('#opc-new-shipping-address').find('[name="street['+index+']"]').val(address).trigger('change');
            });
            self._bindTelephone();
            $('#opc-new-shipping-address').find('[name="country_id"]').val(self.address().countryId).trigger('change');
            $('#opc-new-shipping-address').find('#id').val(self.address().customerAddressId);
            $('#opc-new-shipping-address').find('[name="region_id"]').val(self.address().regionId).trigger('change');
            $('#opc-new-shipping-address').find('[name="city"]').val(self.address().city).trigger('change');
            $('#opc-new-shipping-address').find('div.field-error').hide();
        },
        /**
         *
         * @private
         */
        _bindTelephone: function(){
            var self = this;
            var telephoneNumber = self.address().telephone.replace($('#opc-new-shipping-address').find('.mobile-country-code').val(),'');
            $('#opc-new-shipping-address').find('#ShipMobile').val(telephoneNumber);
        },
        _clearFormData: function (){
            $('.opc-create-shipping-address').find('input[type="text"]:not(".mobile-country-code")').val('');
            $('.opc-create-shipping-address').find('input#id').val('');
            $('.opc-create-shipping-address').find('input#ShipMobile').val('');
            $('.opc-create-shipping-address').find('select').val('');
            $('.opc-create-shipping-address').find('[name="country_id"]').val(window.defaultCountry).trigger('change');
            $('.opc-create-shipping-address').find('[name="region_id"]').val('').trigger('change');
            $('.opc-create-shipping-address').find('#region').hide();
        },
        deleteAddress: function (addressId) {
            var self = this;
            self.addressId = addressId;
            self.deleteUrlPrefix = $('#checkout').data('address');
            $('.opc-wrapper .required_fields_txt,.opc-wrapper .checkout-shipping-address .actions-toolbar,.opc-create-shipping-address').addClass('hidden');
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
                                                if(!$('.shipping-address-item:not(.hidden)').length) {
                                                    self._clearFormData();
                                                    $('.opc-new-shipping-address,.required_fields_txt,.actions-toolbar').show();
                                                    $('.new-address-popup').hide();
                                                    $('.checkout-shipping-address > .step-title').text($t('Shipping Address'));
                                                    $('.opc-wrapper .required_fields_txt,.opc-wrapper .checkout-shipping-address .actions-toolbar,.opc-create-shipping-address').removeClass('hidden');
                                                    $( "#opc-new-shipping-address" ).appendTo($(".add-new-address-form-popup"));
                                                    $(".add-new-address-form-popup").show();
                                                }
                                            }
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
