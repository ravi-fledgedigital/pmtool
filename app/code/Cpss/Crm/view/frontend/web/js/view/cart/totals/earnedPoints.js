/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Cpss_Crm/js/view/summary/earnedPoints'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Cpss_Crm/cart/totals/earnedPoints'
        },
        isEnabled: function () {
            return window.checkoutConfig.enabled;
        }
    });
});
