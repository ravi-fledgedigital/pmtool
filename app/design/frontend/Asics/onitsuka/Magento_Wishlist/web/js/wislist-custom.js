/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'jquery-ui-modules/widget'
], function ($, $t, modal) {
    'use strict';

    $.widget('mage.wishlistCustom', {
        /** @inheritdoc */
        _create: function () {
            $(this.element).find('a[data-role="remove-wislist-custom"]').off('click').on('click', function (e) {
                $('.popup-confirm-delete').data('wishlist',$(e.currentTarget).parents('li.product-item').attr('id'));
                $('.popup-confirm-delete').show();
                e.preventDefault;
                return false;
            });

            $('#popup_ok').off('click').on('click', function (e) {
                $('.popup-confirm-delete').hide();
                $('.products-grid .product-item#'+$('.popup-confirm-delete').data('wishlist')+' #remove-wishlist-items').trigger('click')
                return false;
            });
            $('#popup_cancel').off('click').on('click', function (e) {
                $('.popup-confirm-delete').hide();
                return false;
            });
        }
    });

    return $.mage.wishlistCustom;
});
