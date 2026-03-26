/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'ko',
    'mageUtils',
    'uiComponent',
    'uiLayout',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/address-list',
], function (_, ko, utils, Component, layout, customer, addressList) {
    'use strict';
    var mixin = {
        /** @inheritdoc */
        initialize: function () {
            this._super()
                .initChildren();

            addressList.subscribe(function (changes) {
                    var self = this;

                    changes.forEach(function (change) {
                        self.createRendererComponent(change.value, change.index);
                    });
                },
                this,
                'arrayChange'
            );

            return this;
        },
        checkAddress: function () {
            if(customer.isLoggedIn()){
                if(customer.getShippingAddressList().length){
                    return true;
                }
            }
            return false;
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
