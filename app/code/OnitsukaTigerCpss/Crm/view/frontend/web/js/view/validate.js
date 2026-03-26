define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'OnitsukaTigerCpss_Crm/js/model/validate'
    ],
    function (Component, additionalValidators, orderCustomValidation) {
        'use strict';

        // Register the custom validation logic
        additionalValidators.registerValidator(orderCustomValidation);

        // Extend the component
        return Component.extend({});
    }
);
