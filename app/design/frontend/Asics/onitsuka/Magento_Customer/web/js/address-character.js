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

    $.widget('mage.addressValidationCharacter', {
        options: {
            fieldClass: '.street',
            fieldValidateClass: '.validattion-address',
        },

        /** @inheritdoc */
        _create: function () {
            var self = this;
            $(this.options.fieldClass).find('input').on('change', function (e) {
                self._bindTelephone();
            });
        },
        /**
         * init Address Popup Modal
         */
        _bindTelephone: function () {
            var valueAddress = [];
            $(this.options.fieldClass).find('input').each(function () {
                valueAddress.push($(this).val());
            });
            $(this.options.fieldValidateClass).find('input').val(valueAddress.join(' ').trim()).trigger('change');
        },

    });

    return $.mage.addressValidationCharacter;
});
