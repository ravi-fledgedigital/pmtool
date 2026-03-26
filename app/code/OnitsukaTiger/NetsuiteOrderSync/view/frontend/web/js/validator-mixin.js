define([
    'jquery',
    'moment'
], function ($, moment) {
    "use strict";

    return function (validator) {
        validator.addRule(
            'xml-characters-validate',
            function (value) {
                if (value == '') {
                    return true;
                }
                var validRegex = /([\x00\x09\x0A\x0D\x20-\u{D7FF}\u{E000}-\u{FFFC}\u{10000}-\u{10FFFF}])/umg,
                    invalidRegex = /([\x01-\x08\x0B\x0E-\x1F\x7F-\x84\x86-\x9F\u{FDD0}-\u{FDEF}\u{1FFFE}-\u{1FFFF}\u{2FFFE}-\u{2FFFF}\u{3FFFE}-\u{4FFFF}\u{5FFFE}-\u{5FFFF}\u{6FFFE}-\u{6FFFF}\u{7FFFE}-\u{7FFFF}\u{8FFFE}-\u{8FFFF}\u{9FFFE}-\u{9FFFF}\u{AFFFE}-\u{AFFFF}\u{BFFFE}-\u{BFFFF}\u{CFFFE}-\u{CFFFF}\u{DFFFE}-\u{DFFFF}\u{EFFFE}-\u{EFFFF}\u{FFFFE}-\u{FFFFF}\u{10FFFE}-\u{10FFFF}])/umg;

                return validRegex.test(value) && !invalidRegex.test(value);
            },
            $.mage.__('Please use only letters, numbers (0-9), spaces and "#" in this field.')
        );

        return validator;
    }
});
