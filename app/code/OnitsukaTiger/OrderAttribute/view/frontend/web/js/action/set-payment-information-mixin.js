define([
    'jquery',
    'mage/utils/wrapper',
    'OnitsukaTiger_OrderAttribute/js/model/attribute-sets/payment-attributes',
    'OnitsukaTiger_OrderAttribute/js/model/validate-and-save'
], function ($, wrapper, attributesForm, validateAndSave) {
    'use strict';

    return function (placeOrderAction) {
        return wrapper.wrap(placeOrderAction, function (originalAction, messageContainer, paymentData) {
            var result = $.Deferred();

            validateAndSave(attributesForm).done(
                function() {
                    $.when(
                        originalAction(messageContainer, paymentData)
                    ).fail(
                        function() {
                            result.reject.apply(this, arguments);
                        }
                    ).done(
                        function() {
                            result.resolve.apply(this, arguments);
                        }
                    );
                }
            ).fail(
                function () {
                    result.reject();
                }
            );

            return result.promise();
        });
    };
});
