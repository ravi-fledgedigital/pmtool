/**
 *   Scroll to tabs widget
 */

define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    $.widget('mage.scrollToTabs', {
        options: {
            scrollToTabs: true,
            scrollToTabsDuration: 300,
            offsetTop: 30
        },

        /**
         * Widget initialization
         * @private
         *
         * @returns {void}
         */
        _create: function () {
            if (this.options.scrollToTabs) {
                this._scrollToTabs();
            }
        },

        /**
         * @private
         * @returns {void}
         */
        _scrollToTabs: function () {
            var $element;

            if (!window.location.hash) {
                return;
            }

            $element = $('[href="' + location.hash + '"]');

            if ($element.length) {
                $('html, body').stop().animate({
                    scrollTop: $element.offset().top - this.options.offsetTop
                }, this.options.scrollToTabsDuration, function() {
                    $element.trigger('click');
                });
            }
        }
    });

    return $.mage.scrollToTabs;
});
