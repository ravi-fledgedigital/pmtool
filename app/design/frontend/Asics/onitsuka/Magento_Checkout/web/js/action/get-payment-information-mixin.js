define([
    'mage/utils/wrapper',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/model/payment-service'
], function (wrapper , $, quote, urlBuilder, storage, errorProcessor, customer, methodConverter, paymentService) {
    'use strict';

    return function (proceedToCheckoutFunction) {
        return wrapper.wrap(proceedToCheckoutFunction, function (originalProceedToCheckoutFunction, deferred, messageContainer) {
            var serviceUrl;

            deferred = deferred || $.Deferred();

            /**
             * Checkout for guest and registered customer.
             */
            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/payment-information', {
                    cartId: quote.getQuoteId()
                });
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine/payment-information', {});
            }

            return storage.get(
                serviceUrl, false
            ).done(function (response) {
                var isShippingAddressExists = quote.shippingAddress() &&
                    typeof(quote.shippingAddress()['postcode']) !='undefined' &&
                    typeof(quote.shippingAddress()['city']) !='undefined' &&
                    typeof(quote.shippingAddress()['firstname']) !='undefined' &&
                    typeof(quote.shippingAddress()['street']) !='undefined' &&
                    (typeof(quote.shippingAddress()['region']) !='undefined' ||  typeof(quote.shippingAddress()['regionId']) !='undefined') &&
                    typeof(quote.shippingAddress()['telephone']) !='undefined';
                quote.setTotals(response.totals);
                if(isShippingAddressExists) {
                    paymentService.setPaymentMethods(methodConverter(response['payment_methods']));
                }
                deferred.resolve();
            }).fail(function (response) {
                errorProcessor.process(response, messageContainer);
                deferred.reject();
            });
        });
    };
});
