define([
    'underscore',
    'Magento_Ui/js/form/element/textarea',
    'OnitsukaTiger_OrderAttribute/js/form/relationAbstract'
], function (_, TextArea, relationAbstract) {
    'use strict';

    return TextArea.extend(relationAbstract).extend({
        isFieldInvalid: function () {
            return this.error() && this.error().length ? this : null;
        }
    });
});
