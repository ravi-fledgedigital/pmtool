define(function () {
    'use strict';

    var mixin = {
        getProductTemplate: function () {
            return 'OnitsukaTigerKorea_Rma/return/product';
        },
    };

    return function (target) {
        return target.extend(mixin);
    };
});
