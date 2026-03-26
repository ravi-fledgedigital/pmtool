define([
    'jquery',
    'relatedProducts'
], function ($) {
    'use strict';

    $.widget('mage.amRelatedProducts', $.mage.relatedProducts, {
        _create: function () {
            this.element.find('[role="select-all"]').off('click');
            this.element.find('.checkbox.related')
                .removeClass('related')
                .addClass('am-tab-related');
            this._super();
        }
    });

    return $.mage.amRelatedProducts;
});
