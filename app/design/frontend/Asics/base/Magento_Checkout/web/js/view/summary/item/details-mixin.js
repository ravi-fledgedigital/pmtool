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
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
