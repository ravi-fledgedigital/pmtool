define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/additional-validators',
    'OnitsukaTigerKorea_Checkout/js/model/year-old-age-validator'
], function (Component, additionalValidators, yearOldAgeValidator) {
    'use strict';

    additionalValidators.registerValidator(yearOldAgeValidator);

    return Component.extend({});
});