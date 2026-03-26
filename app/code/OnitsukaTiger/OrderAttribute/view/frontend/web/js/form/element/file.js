define([
    'underscore',
    'Magento_Ui/js/form/element/file-uploader',
    'OnitsukaTiger_OrderAttribute/js/form/relationAbstract'
], function (_, Abstract, relationAbstract) {
    'use strict';

    // relationAbstract - attribute dependencies
    return Abstract.extend(relationAbstract).extend({

        initialize: function () {
            this._super();

            this.inputName = this.index;

            return this;
        },

        /**
         * @param {Object} file
         * @returns {Object}
         */
        processFile: function (file) {
            file.previewType = this.getFilePreviewType(file);

            if (!file.id && file.name) {
                file.id = btoa(unescape(encodeURIComponent(file.name)));
            }

            this.observe.call(file, true, [
                'previewWidth',
                'previewHeight'
            ]);

            return file;
        },

        /**
         * Retrieves preview element value
         *
         * @returns {boolean|string}
         */
        getPreviewValue: function () {
            if (_.isEmpty(this.value())) {
                return false;
            }

            return _.first(this.value()).name;
        }
    });
});
