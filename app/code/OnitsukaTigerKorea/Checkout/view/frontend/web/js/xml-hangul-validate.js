define([
    'jquery',
    'jquery/validate',
    'mage/translate'
], function($){
    'use strict';
    return function() {
        $.validator.addMethod(
            "xml-hangul-validate",
            function(value, element) {
                if (value == '') {
                    return true;
                }
                var validHangulRegex = /^[\u3131-\u3163\uac00-\ud7a3 A-Za-z]+$/g
                return validHangulRegex.test(value);
            },
            $.mage.__("Please use only letters Korean and Alphabet in this field")

        );
    }
});
