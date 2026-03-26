/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function ($, confirm) {
    'use strict';

    /**
     * @param {String} url
     * @returns {jQuery}
     */
    function getForm(url) {
        return $('<form>', {
            'action': url,
            'method': 'POST'
        }).append($('<input>', {
            'name': 'form_key',
            'value': window.FORM_KEY,
            'type': 'hidden'
        }));
    }

    $('#invitation-view-send-invitation-button').on('click', function () {
        const msg = $.mage.__('Are you sure you want to do this?'),
            url = $('#invitation-view-send-invitation-button').data('url');

        confirm({
            'content': msg,
            'actions': {

                /**
                 * 'Confirm' action handler.
                 */
                confirm: function () {
                    getForm(url).appendTo('body').trigger('submit');
                }
            }
        });

        return false;
    });
});
