/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/mage',
    'Magento_Catalog/product/view/validation',
    'catalogQuickPurchase'
], function ($) {
    'use strict';

    $.widget('mage.productValidate', {
        options: {
            bindSubmit: false,
            quickPurchaseButtonSelector: '.action.quick-purchase'
        },

        /**
         * Uses Magento's validation widget for the form object.
         * @private
         */
        _create: function () {
            var bindSubmit = this.options.bindSubmit;

            $(this.options.quickPurchaseButtonSelector).on("click", function (e) {
                var jqForm = $('#product_addtocart_form').catalogQuickPurchase({
                    bindSubmit: bindSubmit
                });

                jqForm.catalogQuickPurchase('submitForm', jqForm);
            });

            $(this.options.quickPurchaseButtonSelector).prop("disabled", false);
        }
    });

    return $.mage.productValidate;
});
