/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'mage/translate'
], function (Component, quote, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'OnitsukaTigerKorea_Checkout/summary/giftPackging'
        },
        /**
         * Get discount title
         *
         * @returns {null|String}
         */
        getTitle: function () {
            return $t('Gift packaging');
        },
    });
});
