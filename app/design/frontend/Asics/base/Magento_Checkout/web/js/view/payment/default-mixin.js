define([
        'ko',
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'uiRegistry',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/model/messages',
        'uiLayout',
        'Magento_Checkout/js/action/redirect-on-success'
    ], function (
    ko,
    $,
    Component,
    placeOrderAction,
    selectPaymentMethodAction,
    quote,
    customer,
    paymentService,
    checkoutData,
    checkoutDataResolver,
    registry,
    additionalValidators,
    Messages,
    layout,
    redirectOnSuccessAction
    ) {
        'use strict';

        var mixin = {
            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;
                if(!this.isPlaceOrderActionAllowed()){
                    $('.checkout-payment-method .payment-method._active .checkout-billing-address .primary .action-update').trigger('click');
                }
                if (event) {
                    event.preventDefault();
                }

                if (this.validate() &&
                    additionalValidators.validate() &&
                    this.isPlaceOrderActionAllowed() === true
                ) {
                    if(customer.isLoggedIn()){
                        document.cookie = 'checkoutStepCurrent=shipping; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                    }else{
                        document.cookie = 'checkoutStepCurrent=isLogedCheck; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                    }
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .done(
                            function () {
                                self.afterPlaceOrder();

                                if (self.redirectAfterPlaceOrder) {
                                    redirectOnSuccessAction.execute();
                                }
                            }
                        ).always(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    );

                    return true;
                }

                return false;
            },
            isChecked: ko.computed(function () {
                return quote.paymentMethod() ? quote.paymentMethod().method : 'omise_cc';
            }),
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);
