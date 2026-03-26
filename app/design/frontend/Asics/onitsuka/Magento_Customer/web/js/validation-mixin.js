define([
    'jquery'
], function ($) {
    "use strict";

    return function () {
        $.validator.addMethod(
            'validate-character-length',
            function (v, elm) {
                var reMax = new RegExp(/^maximum-character-length-[0-9]+$/),
                    reMin = new RegExp(/^minimum-character-length-[0-9]+$/),
                    validator = this,
                    result = true,
                    length = 0;

                $.each(elm.className.split(' '), function (index, name) {
                    if (name.match(reMax) && result) {
                        length = name.split('-')[3];
                        result = v.length <= length;
                        validator.validateMessage =
                            $.mage.__('Please reduce word characters to fit text field (%1 characters ).').replace('%1', length);
                    }

                    if (name.match(reMin) && result && !$.mage.isEmpty(v)) {
                        length = name.split('-')[3];
                        result = v.length >= length;
                        validator.validateMessage =
                            $.mage.__('Please increase word characters to fit text field (%1 characters ).').replace('%1', length);
                    }
                });
                return result;
            }, function () {
                return this.validateMessage;
            }
        );
        $.validator.addMethod(
            'validate-character-address',
            function (v, elm) {
                var validator = this,
                    result = true,
                    params = $(elm).data('character');

                $.each(params.split(''), function (index, name) {
                    if(v.includes(name) && result) {
                        result = !v.includes(name);
                        validator.validateMessage =
                            $.mage.__('Please Ignore %1 characters.').replace('%1', name);
                    }
                });
                return result;
            }, function () {
                return this.validateMessage;
            }
        );
    }
});
