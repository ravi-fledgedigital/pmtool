define([
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/payment/additional-validators',
    'mage/validation'
], function ($, Component, additionalValidators) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();

            additionalValidators.registerValidator(this);
            return this;
        },

        /**
         * This runs automatically before Place Order
         */
        validate: function () {
            var checkbox = $('#vatInvoice');

            if (!checkbox.is(':checked')) {
                return true;
            }

            var form = $('#vat-invoice-form');
            form.validation();
            return form.validation('isValid');
        }
    });
});
