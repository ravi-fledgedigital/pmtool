define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'OnitsukaTiger_OrderAttribute/js/model/attributes-validator'
    ],
    function (Component, additionalValidators, attributeValidator) {
        'use strict';

        additionalValidators.registerValidator(attributeValidator);
        return Component.extend({});
    }
);
