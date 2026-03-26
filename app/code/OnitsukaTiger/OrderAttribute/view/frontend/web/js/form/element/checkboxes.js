define([
    'ko',
    'underscore',
    'mageUtils',
    'Magento_Ui/js/form/element/abstract',
    'OnitsukaTiger_OrderAttribute/js/form/relationAbstract'
], function (ko, _, utils, Abstract, relationAbstract) {
    'use strict';

    function indexOptions(data, result) {
        var value;

        result = result || {};
        data.forEach(function (item) {
            value = item.value;
            if (Array.isArray(value)) {
                indexOptions(value, result);
            } else {
                result[value] = item;
            }
        });

        return result;
    }

    // relationAbstract - attribute dependencies
    return Abstract.extend(relationAbstract).extend({
        isFieldInvalid: function () {
            return this.error() && this.error().length ? this : null;
        },

        /**
         * Calls 'initObservable' of parent, initializes 'options' and 'initialOptions'
         *     properties, calls 'setOptions' passing options to it
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            var defaultValue = this.value;
            this._super();
            var value = this.value;
            this.value = ko.observableArray([]).extend(value);
            this.value(this.normalizeData(defaultValue));
            this.indexedOptions = indexOptions(this.options);

            return this;
        },

        /**
         * Splits incoming string value.
         *
         * @returns {Array}
         */
        normalizeData: function (value) {
            if (utils.isEmpty(value)) {
                value = [];
            }

            return _.isString(value) ? value.split(',') : value;
        },

        /**
         * Defines if value has changed
         *
         * @returns {Boolean}
         */
        hasChanged: function () {
            var value = this.value(),
                initial = this.initialValue;

            return !utils.equalArrays(value, initial);
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
