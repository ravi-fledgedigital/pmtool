define([
    'OnitsukaTiger_OrderAttribute/js/action/onitsukatiger-validate-form',
    'OnitsukaTiger_OrderAttribute/js/model/attribute-sets/payment-attributes'
], function (validateForm, formData) {
    'use strict';

    return {
        /**
         * Validate checkout agreements
         *
         * @returns {boolean}
         */
        validate: function() {
            window.orderAttributesPreSend = validateForm(formData.attributeTypes);
            return window.orderAttributesPreSend;
        }
    }
});
