define([
        'jquery',
        'underscore',
        'ko',
        'uiComponent',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/view/billing-address',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer'
    ],
    function (
        $,
        _,
        ko,
        Component,
        stepNavigator,
        billingAddress,
        quote,
        customer
    ) {
        'use strict';
        var mixin = {
            initialize: function () {
                var hashString = window.location.hash.replace('#', '');
                var cookiesValue = this._getCookies('shippingAddressData');
                if(cookiesValue && cookiesValue != window.checkoutConfig.defaultCountryId){
                    if (customer.isLoggedIn()) {
                        window.location = window.checkoutConfig.checkoutUrl + '#shipping';
                    } else {
                        window.location = window.checkoutConfig.checkoutUrl + '#isLogedCheck';
                    }
                }else{
                    if (hashString === 'payment') {
                        window.location = window.checkoutConfig.checkoutUrl + '#shipping';
                    }
                }
                this._super();
            },
            _getCookies: function(cname){
                var name = cname + "=";
                var decodedCookie = decodeURIComponent(document.cookie);
                var ca = decodedCookie.split(';');
                for(var i = 0; i <ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') {
                        c = c.substring(1);
                    }
                    if (c.indexOf(name) == 0) {
                        return c.substring(name.length, c.length);
                    }
                }
                return "";
            },
            /**
             * @param {Object} step
             */
            navigateTo: function (step) {
                if (step.code !== 'payment') {
                    $('body').removeClass('pickup-store-payment');
                    if (step.code === 'shipping') {
                        billingAddress().needCancelBillingAddressChanges();
                    }
                }

                stepNavigator.navigateTo(step.code);
            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);
