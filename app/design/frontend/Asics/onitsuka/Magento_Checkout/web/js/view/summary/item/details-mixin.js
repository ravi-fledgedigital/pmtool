define([
    'uiComponent',
    'Magento_Customer/js/model/customer',
    'jquery',
    'ko'
], function (Component, customer, $, ko) {
    'use strict';

    var mixin = {
        isCustomerLoggedIn: function () {
            return customer.isLoggedIn();
        },
        getSizeForDisplay: function (id) {
            var size = null,
                itemData = window.checkoutConfig.totalsData.items;
            _.each(itemData, function(element, index) {
                if (element.item_id == id && element.size_for_display) {
                    size = element.size_for_display;
                }
            });
            return size;
        },
        getColor: function (id) {
            var color = null,
                itemData = window.checkoutConfig.totalsData.items;
            _.each(itemData, function(element, index) {
                if (element.item_id == id && element.color) {
                    color = element.color;
                }
            });
            return color;
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
