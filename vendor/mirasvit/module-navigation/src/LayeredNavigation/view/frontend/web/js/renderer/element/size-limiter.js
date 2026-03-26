define([
    "jquery",
    "domReady!"
], function ($) {
    'use strict';

    $.widget('mst.navSizeLimiter', {
        options: {
            limit:    5,
            textLess: '',
            textMore: ''
        },

        _create: function () {
            this.hideOptions();

            var $toggle = $('[data-element = sizeToggle]', this.element);

            $toggle.on('click', function () {
                if ($toggle.html() === this.options.textMore) {
                    $('[data-element = filter]', this.element).attr('data-hidden', 'false');
                    $toggle.html(this.options.textLess);
                } else {
                    this.hideOptions();
                    $toggle.html(this.options.textMore);
                }
            }.bind(this));

            $(this.element).on('showhide', function (e) {
                if ($('[data-element = search]', e.target).val() || $('.mst-nav__alphabetical span._checked', e.target.parentElement).length) {
                    $toggle.hide()
                } else {
                    $toggle.html(this.options.textLess);
                    $toggle.show()
                }
            }.bind(this));
        },

        hideOptions: function () {
            var items = $('[data-element="filter"]', this.element);
            let limit = this.options.limit;

            items.attr('data-hidden', 'true');

            items.each(function (idx, elem) {
                if (limit == 0) {
                    return;
                }

                elem = $(elem);
                elem.attr('data-hidden', 'false');

                if (elem.attr('data-value')) {
                    limit--;
                }
            });
        }
    });

    return $.mst.navSizeLimiter;
});
