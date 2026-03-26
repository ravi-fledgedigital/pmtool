define([
    "jquery",
    "Magento_Ui/js/modal/modal",
    "jquery/ui"
], function ($, modal) {
    'use strict';
    $.widget('mage.ot_preorders_product', {
        options: {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            buttons: []
        },
        _create: function () {
            let popup = modal(this.options, $('#' + this.options.popupBlockId));
            this.initEventListeners(this.options.buttonId);
        },
        initEventListeners: function (buttonId) {
            let self = this;
            $('#' + buttonId).click(function () {
                self.openPopup();
            });
        },
        openPopup: function () {
            $('#popup-modal').modal('openModal');
        }
    });
    return $.mage.ot_preorders_product;
});