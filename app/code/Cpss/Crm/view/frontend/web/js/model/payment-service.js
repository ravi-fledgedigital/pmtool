/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment/method-list',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/checkout-data',
     'jquery',
], function (_, quote, methodList, selectPaymentMethod, checkoutData, $) {
    'use strict';

    /**
    * Free method filter
    * @param {Object} paymentMethod
    * @return {Boolean}
    */
    var isFreePaymentMethod = function (paymentMethod) {
            return paymentMethod.method === 'free';
        },

        isFullpointPaymentMethod = function (paymentMethod) {
             return paymentMethod.method === 'fullpoint';
         },

        /**
         * Grabs the grand total from quote
         * @return {Number}
         */
        getGrandTotal = function () {
            return quote.totals()['grand_total'];
        };

    return {
        isFreeAvailable: false,
        isFullpointAvailable: false,

        /**
         * Populate the list of payment methods
         * @param {Array} methods
         */
        setPaymentMethods: function (methods) {
            var freeMethod,
                fullPoint,
                filteredMethods,
                methodIsAvailable,
                filterMethod = [],
                methodNames;

            freeMethod = _.find(methods, isFreePaymentMethod);
            this.isFreeAvailable = !!freeMethod;

            fullPoint = _.find(methods, isFullpointPaymentMethod);
            var how_to_use = $("input[name='how_to_use[]']:checked").val();

            if(fullPoint && getGrandTotal() <= 0 && how_to_use === "use_all") {
                this.isFullpointAvailable = !!fullPoint;
                methods.splice(0, methods.length, fullPoint);
                selectPaymentMethod(fullPoint);
                filterMethod = fullPoint;
            } else if (freeMethod && getGrandTotal() <= 0 && checkoutData.getSelectedPaymentMethod() != "fullpoint") {
                methods.splice(0, methods.length, freeMethod, fullPoint);
                selectPaymentMethod(freeMethod);
                filterMethod = freeMethod;
            }

            filteredMethods = _.without(methods, filterMethod);
            if (filteredMethods.length === 1 && how_to_use === "undefined") {
                selectPaymentMethod(filteredMethods[0]);
            } else if (quote.paymentMethod()) {
                methodIsAvailable = methods.some(function (item) {
                    return item.method === quote.paymentMethod().method;
                });
                //Unset selected payment method if not available
                if(how_to_use === "use_all" && getGrandTotal() > 0) {
                    selectPaymentMethod(null);
                    this.isFullpointAvailable = false;
                }
                if (!methodIsAvailable) {
                    selectPaymentMethod(null);
                }
            }

            /**
             * Overwrite methods with existing methods to preserve ko array references.
             * This prevent ko from re-rendering those methods.
             */
            methodNames = _.pluck(methods, 'method');
            _.map(methodList(), function (existingMethod) {
                var existingMethodIndex = methodNames.indexOf(existingMethod.method);

                if (existingMethodIndex !== -1) {
                    methods[existingMethodIndex] = existingMethod;
                }
            });

            methodList(methods);
        },

        /**
         * Get the list of available payment methods.
         * @return {Array}
         */
        getAvailablePaymentMethods: function () {
            var allMethods = methodList().slice(),
                grandTotalOverZero = getGrandTotal() > 0;

            if (checkoutData.getSelectedPaymentMethod() === "fullpoint") {
                return allMethods;
            }

            if (isFullpointPaymentMethod && grandTotalOverZero) {
                return _.reject(allMethods, isFullpointPaymentMethod);
            }

            $('.fullpoint-parent').hide();
            if(this.isFullpointAvailable) {
                $('.fullpoint-parent').show();
                return _.filter(allMethods, isFullpointPaymentMethod);
            }

            if (!this.isFreeAvailable) {
                return allMethods;
            }

            if (grandTotalOverZero) {
                return _.reject(allMethods, isFreePaymentMethod);
            }

            return _.filter(allMethods, isFreePaymentMethod);
        }
    };
});
