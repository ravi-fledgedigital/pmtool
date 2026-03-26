define([
    'jquery',
    'mage/validation',
    'Magento_Ui/js/form/element/select',      
    'Magento_Ui/js/modal/modal'
], function ($) {
    'use strict';

    return {
        /**
         * Validate checkout agreements
         *
         * @returns {Boolean}
         */
        validate: function (hideError) {

            var isValid = true,
            element = $('.payment-method._active div.year-old-age input');
            $('#billingAddress-error').remove();
            
            if (!$.validator.validateSingleElement(element, {
                errorElement: 'div',
                hideError: hideError || false
            })) {
                isValid = false;
                $('#billingAddress-error').addClass('error-btm-space');
            }

            return isValid;
        }
    };
});