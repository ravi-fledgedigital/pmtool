define(
    [
        'Omise_Payment/js/view/payment/omise-base-method-renderer',
        'mage/storage',
        'jquery',
        'Magento_Customer/js/model/customer',
    ],
    function (
        Base,
        storage,
        $,
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
                var
                    self = this,
                    buildFailHandler = this.buildFailHandler,
                    failHandler = buildFailHandler(self)
                ;
                if(!this.isPlaceOrderActionAllowed()){
                    $('.checkout-payment-method .payment-method._active .checkout-billing-address .primary .action-update').trigger('click');
                    return false;
                }
                if(customer.isLoggedIn()){
                    document.cookie = 'checkoutStepCurrent=shipping; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                }else{
                    document.cookie = 'checkoutStepCurrent=isLogedCheck; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                }
                event && event.preventDefault();

                self.getPlaceOrderDeferredObject()
                    .fail(failHandler)
                    .done(function (order_id) {
                        var
                            storageFailHandler = buildFailHandler(this),
                            serviceUrl = self.getMagentoReturnUrl(order_id)
                        ;
                        storage.get(serviceUrl, false)
                            .fail(storageFailHandler)
                            .done(function (response) {
                                if (response) {
                                    $.mage.redirect(response.authorize_uri);
                                } else {
                                    storageFailHandler(response);
                                }
                            });
                    });

                return true;
            }

        });

    }
);
