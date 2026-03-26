/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Customer/js/model/customer',
    'mage/translate',
], function ($, customer) {
    'use strict';

    var mixin = {

        getCustomerEmail: function () {
            return customer.customerData.email;
            $('.checkout-shipping-address .step-title').text('SAVED ADDRESSES');
        },
        getAddressType: function (address) {
            return $t('home');
        },
        getAddressLine : function (street) {
            var htmlAddress = ''
            for (var i=0;i<street.length;i++){
                htmlAddress += '<span>' +  street[0]+'</span>,</br>';
            }
            return htmlAddress;
        },
        getAddressPhone: function (phone) {
            if(typeof(window.telephonePrefix) !='undefined') {
                return window.telephonePrefix + '-' +phone.substring(2);
            }
            return phone;
        },
        selectedAddress:function () {
            $('.shipping-address-item').removeClass('selected-item not-selected-item');
            $(event.currentTarget).addClass('selected-item')
        },
        selectAddress: function () {
            this._super();
            $('#shipping-method-buttons-container button').trigger('click')
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
