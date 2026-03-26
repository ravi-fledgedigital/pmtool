define([
    'jquery',
    'mage/tooltip'
], function ($) {
    'use strict';

    $.widget('mst.navHelperTooltip', {
        options: {
            tooltip: ''
        },

        _create: function () {
            const $tooltip = this.tooltip()

            if (!$tooltip) {
                return
            }

            const $title = $(this.element).closest('.filter-options-item').find('.filter-options-title');

            if ($('.mst-nav__tooltip-holder', $title).length) {
                $('.mst-nav__tooltip-holder', $title).remove();
            }

            $title.append($tooltip)
        },

        tooltip() {
            const text = this.options.tooltip
            if (!text || text.trim() === '') {
                return false
            }

            const $tooltip = $('<div />')
                .addClass('mst-nav__tooltip-holder')
                .attr('data-tooltip', text)

            $tooltip.tooltip({
                items:        '*',
                tooltipClass: 'mst-nav__tooltip-wrapper',
                content:      function () {
                    const $el = $(this)
                    return $el.attr('data-tooltip')
                }
            })

            return $tooltip
        }
    });

    return $.mst.navHelperTooltip;
});
