define(
    [
        'ko'
    ], function (ko) {
        'use strict';

        var mixin = {
            /**
             * @return {*}
             */
            isFullMode: function () {
                return true;
            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);
