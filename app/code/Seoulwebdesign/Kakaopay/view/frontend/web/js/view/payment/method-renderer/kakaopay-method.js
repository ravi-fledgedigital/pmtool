define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function (
        $,
        Component,
        setPaymentInformationAction,
        checkoutData,
        quote,
        customer,
        urlBuilder,
        placeOrderService,
        fullScreenLoader,
        redirectOnSuccessAction
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Seoulwebdesign_Kakaopay/payment/kakaopay',
                redirectAfterPlaceOrder: false
            },

            getCode: function () {
                return 'kakaopay';
            },

            isActive: function () {
                var self = this;
                return true;
            },

            initialize: function () {
                var self = this;
                this._super();
            },

            getInstructions: function () {
                return window.checkoutConfig.payment[this.getCode()].instructions
            },

            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                var self = this;
                fullScreenLoader.startLoader();
                self.isPlaceOrderActionAllowed(false);
                $.ajax({
                    type: 'POST',
                    url: window.checkoutConfig.payment[self.getCode()].redirectUrl,
                    dataType: "json",
                    async: false,
                    success: function (response) {
                        if (response.success) {
                            window.location.href = response.payUrl;
                        } else {
                            fullScreenLoader.stopLoader();
                            self.messageContainer.addErrorMessage({
                                message: "Error, please try again"
                            });
                        }
                    },
                    error: function (response) {
                        fullScreenLoader.stopLoader();
                        self.messageContainer.addErrorMessage({
                            message: "Error, please try again"
                        });
                    }
                });
            }
        });
    }
);
