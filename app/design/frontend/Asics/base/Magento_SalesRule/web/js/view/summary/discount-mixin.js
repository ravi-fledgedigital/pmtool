define(
    [
        'ko'
    ], function (ko) {
        'use strict';

        var mixin = {

            /**
             * @return {*|String}
             */
            getValue: function () {
                return this.getFormattedPrice((this.getPureValue() * (-1)));
            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);
