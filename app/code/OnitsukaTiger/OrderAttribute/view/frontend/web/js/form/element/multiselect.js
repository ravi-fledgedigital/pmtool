define([
    'underscore',
    'Magento_Ui/js/form/element/multiselect',
    'OnitsukaTiger_OrderAttribute/js/form/relationAbstract'
], function (_, Multiselect, relationAbstract) {
    'use strict';

    return Multiselect.extend(relationAbstract).extend({
        isFieldInvalid: function () {
            return this.error() && this.error().length ? this : null;
        },

        /**
         * Retrieves preview element value
         *
         * @returns {boolean|string}
         */
        getPreviewValue: function () {
            var previewValue = false;

            if (_.isEmpty(this.value())) {
                return previewValue;
            }

            if (typeof this.value() === 'object') {
                previewValue = '';
                _.each(this.value(), function (e) {
                    previewValue += ', ' + this.indexedOptions[e].label;
                }.bind(this));
                previewValue = previewValue.substr(2);
            } else {
                previewValue = this.indexedOptions[this.value()].label;
            }

            return previewValue;
        }
    });
});
