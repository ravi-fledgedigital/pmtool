define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'underscore'
], function ($, storage, _) {
    'use strict';

    var cacheKey = 'onitsukatiger-checkout-data',

        /**
         * @param {Object} data
         */
        saveData = function (data) {
            storage.set(cacheKey, data);
        },

        /**
         * @return {*}
         */
        getData = function () {
            var data = storage.get(cacheKey)();

            if ($.isEmptyObject(data)) {
                data = {
                    'onitsukatigerShippingAttributes': null,
                    'onitsukatigerPaymentAttributes': null
                };
                saveData(data);
            }

            return data;
        };

    return {

        setCheckoutData: function (key, data) {
            var obj = getData();

            obj[key] = data;

            saveData(obj);
        },

        getCheckoutData: function (key) {
            return getData()[key];
        }
    };
});
