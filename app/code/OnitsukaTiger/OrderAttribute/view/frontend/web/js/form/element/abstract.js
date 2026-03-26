define([
    'ko',
    'underscore',
    'mageUtils',
    'Magento_Ui/js/form/element/abstract',
    'OnitsukaTiger_OrderAttribute/js/form/relationAbstract'
], function (ko, _, utils, Abstract, relationAbstract) {
    'use strict';

    // relationAbstract - attribute dependencies
    return Abstract.extend(relationAbstract).extend({
        isFieldInvalid: function () {
            return this.error() && this.error().length ? this : null;
        }
    });
});
