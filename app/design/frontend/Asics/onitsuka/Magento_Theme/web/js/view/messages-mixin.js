/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

define([
    'jquery',
    'mage/translate',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'underscore',
    'escaper',
    'jquery/jquery-storageapi'
], function ($, $t, Component, customerData, _, escaper) {
    'use strict';
    var mixin = {
        /**
         * Prepare the given message to be rendered as HTML
         *
         * @param {String} message
         * @return {String}
         */
        prepareMessageForHtmlCode: function (text) {
            if($('<div>').html(text).text().includes('</br>') || $('<div>').html(text).text().includes('<br>') || $('<div>').html(text).text().includes('<br/>')){
                return $('<div>').html(text).text();
            }
            return $('<div>').html(text).html();
        },
        clearMessage:function (message) {
            return true;
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
