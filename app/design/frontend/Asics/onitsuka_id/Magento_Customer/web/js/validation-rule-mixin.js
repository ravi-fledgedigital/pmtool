define([
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate'
], function ($) {
    "use strict";

    return function (validator) {
        validator.addRule(
            'maximum-character-length',
            function (value, params) {
                return !_.isUndefined(value) && value.length <= +params;
            },
            $.mage.__('Please reduce word characters to fit text field ({0} characters ).')
        );
        validator.addRule(
            'validate-character-address',
            function (value, params) {
                var result = true;
                $.each(params.split(''), function (index, name) {
                    if(value.includes(name) && result) {
                        result = !value.includes(name);
                    }
                });
                return result;
            },
            $.mage.__('Please Ignore {0} characters.')
        );
        validator.addRule(
            'validate-input-space',
            function (value) {
                var result = true;
                if (value.indexOf(' ') != -1) {
                    result = false;
                }

                return result;
            },
            $.mage.__('Please enter a valid number in this field.')
        );
        return validator;
    }
});
