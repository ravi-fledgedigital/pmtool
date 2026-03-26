/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'jquery-ui-modules/widget',
], function ($, $t, modal) {
    'use strict';

    $.widget('mage.addressTelephone', {
        options: {
            phoneCountry: '#phone-countrycode',
            mobileNumber: '#mobile-number',
        },

        /** @inheritdoc */
        _create: function () {
            var self = this;
            $(this.options.mobileNumber).on('change', function (e) {
                self._bindTelephone();
            });
        },
        /**
         * init Address Popup Modal
         */
        _bindTelephone: function () {
            var value = this.element.find(this.options.phoneCountry).val() + '' + this.element.find(this.options.mobileNumber).val()
            this.element.find('#telephone').val(value);
        },

    });

    return $.mage.addressTelephone;
});
