define([
    'jquery',
    'mage/utils/wrapper',
    'OnitsukaTiger_OrderAttribute/js/model/attribute-sets/shipping-attributes',
    'OnitsukaTiger_OrderAttribute/js/model/validate-and-save'
], function ($, wrapper, attributesForm, validateAndSave) {
    'use strict';

    if (typeof window.checkoutConfig.otOrderAttribute.sendOnShipping !== "undefined"
        && !window.checkoutConfig.otOrderAttribute.sendOnShipping) {
        return function (setShippingInformationAction) {
            return setShippingInformationAction;
        }
    } else {
        return function (setShippingInformationAction) {
            return wrapper.wrap(setShippingInformationAction, function (originalAction) {
                var result = $.Deferred();

                validateAndSave(attributesForm).done(
                    function () {
                        $.when(
                            originalAction()
                        ).fail(
                            function () {
                                result.reject.apply(this, arguments);
                            }
                        ).done(
                            function () {
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
    }
});
