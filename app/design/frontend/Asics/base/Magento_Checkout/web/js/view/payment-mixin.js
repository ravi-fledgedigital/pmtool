define(
    [
        'ko',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/action/get-payment-information',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'mage/translate'
    ], function (ko,customer,quote,stepNavigator ,paymentService, methodConverter, getPaymentInformation, checkoutDataResolver, $t) {
        'use strict';

        var mixin = {

            initialize: function () {
                this.isVisible = ko.observable(quote.isVirtual()); // set visible to be initially false to have your step show first
                this._super();
                if(!document.cookie.match('checkoutStepCurrent')){
                    if(quote.isVirtual() && customer.isLoggedIn()){
                        stepNavigator.setHash('payment');
                        document.cookie = 'checkoutStepCurrent=payment; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                    }
                }else{
                    var cookiesValue = this._getCookies('checkoutStepCurrent');
                    if(cookiesValue) {
                        if(quote.isVirtual() && customer.isLoggedIn()){
                            stepNavigator.setHash('payment');
                            document.cookie = 'checkoutStepCurrent=payment; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                        }else{
                            stepNavigator.setHash(cookiesValue);
                        }
                    }else{
                        if(quote.isVirtual() && customer.isLoggedIn()){
                            stepNavigator.setHash('payment');
                            document.cookie = 'checkoutStepCurrent=payment; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                        }
                    }
                }
                return this;
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
            navigate: function () {
                var self = this;

                if (quote.isVirtual() && !customer.isLoggedIn()) {
                    this.isVisible(false);
                    stepNavigator.setHash('shipping');
                } else {
                    getPaymentInformation().done(function () {
                        self.isVisible(true);
                    });
                }
            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);
