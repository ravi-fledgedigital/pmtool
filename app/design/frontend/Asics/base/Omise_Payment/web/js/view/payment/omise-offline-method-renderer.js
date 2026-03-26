define(
    [
        'Omise_Payment/js/view/payment/omise-base-method-renderer',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Customer/js/model/customer',
    ],
    function (
        Base,
        redirectOnSuccessAction,
        customer
    ) {
        'use strict';

        return Object.assign({}, Base, {

            /**
             * Hook the placeOrder function.
             * Original source: placeOrder(data, event); @ module-checkout/view/frontend/web/js/view/payment/default.js
             *
             * @return {boolean}
             */
            placeOrder: function (data, event) {
                if(!this.isPlaceOrderActionAllowed()){
                    $('.checkout-payment-method .payment-method._active .checkout-billing-address .primary .action-update').trigger('click');
                    return false;
                }
                var failHandler = this.buildFailHandler(this);

                event && event.preventDefault();
                if(customer.isLoggedIn()){
                    document.cookie = 'checkoutStepCurrent=shipping; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                }else{
                    document.cookie = 'checkoutStepCurrent=isLogedCheck; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                }
                this.getPlaceOrderDeferredObject()
                    .fail(failHandler)
                    .done(function () {
                        redirectOnSuccessAction.execute();
                    });

                return true;
            }
        });
    }
);
