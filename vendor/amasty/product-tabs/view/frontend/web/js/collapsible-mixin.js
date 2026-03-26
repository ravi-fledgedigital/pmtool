/**
 * Mage collapsible mixin
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        $.widget('mage.collapsible', widget, {
            options: {
                selectors: {
                    accordionView: '.amtabs-accordion-view',
                    title: '[data-amtheme-js="item-title"]'
                }
            },

            _open: function () {
                this._super();

                if (this.element.parents(this.options.selectors.accordionView).length
                    && this.element.has(this.options.selectors.title)) {
                    this._scrollToTopIfNotVisible();
                }
            }
        });

        return $.mage.collapsible;
    };
});
