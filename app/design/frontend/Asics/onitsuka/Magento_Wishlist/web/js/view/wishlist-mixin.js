/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function (Component, customerData) {
    'use strict';

    var mixin = {

        /**
         *
         * @param {Column} elem
         */
        getWishlistCounter: function (counterWislist) {
            return counterWislist.replace('items','').replace('item','')
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
