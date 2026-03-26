define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mst.navNestedToggler', {
        letters:         [],
        visibleSelector: '[data-letter][data-value][data-search-hidden = false]',

        options: {
            shouldDisplay: false,
        },

        _create: function () {
            if (!this.options.shouldDisplay) {
                return;
            }

            $('li._parent', this.element).each(function (idx, elem) {
                this.initCollapsibe(elem);
            }.bind(this));

            $(this.element).on('nested-toggle', function (event, q) {
                const $parent = $('ol.items.collapsible', $(this.element));

                if ($parent === undefined || !$parent.length) {
                    return;
                }

                if (q) {
                    $('ol.items, a.mst-nav_category-toggler', $parent).addClass('_active');
                    $('a.mst-nav_category-toggler', $parent).hide();
                } else {
                    $('ol.items, a.mst-nav_category-toggler', $parent).removeClass('_active');
                    $('a.mst-nav_category-toggler', $parent).show();
                }
            }.bind(this))
        },

        initCollapsibe: function (element) {
            const $togglerHandler = $(element);
            const $target         = $togglerHandler.next('ol');
            const $toggler        = $('<a class="mst-nav_category-toggler"><span/></a>');

            $('li', $target).each(function (idx, elem) {
                if ($(elem).hasClass('_checked')) {
                    $target.addClass('_active');
                    $toggler.addClass('_active');
                }
            });

            $toggler.on('click', function (e) {
                $toggler.toggleClass('_active');
                $target.toggleClass('_active');
            });

            $togglerHandler.css({"margin-left": "0"});
            $togglerHandler.before($toggler);
        }
    });

    return $.mst.navNestedToggler;
});
