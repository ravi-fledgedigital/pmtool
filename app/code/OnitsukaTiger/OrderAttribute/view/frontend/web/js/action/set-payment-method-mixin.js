define([
    'jquery',
    'mage/utils/wrapper',
    'OnitsukaTiger_OrderAttribute/js/model/attribute-sets/payment-attributes',
    'OnitsukaTiger_OrderAttribute/js/model/validate-and-save'
], function ($, wrapper, attributesForm, validateAndSave) {
    'use strict';

    return function (placeOrderAction) {
        return wrapper.wrap(placeOrderAction, function (originalAction, messageContainer) {
            if (typeof window.orderAttributesPreSend !== "undefined" && window.orderAttributesPreSend) {
                var result = $.Deferred();

                validateAndSave(attributesForm).done(
                    function() {
                        $.when(
                            originalAction(messageContainer)
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
            }

            return originalAction(messageContainer);
        });
    };
});
