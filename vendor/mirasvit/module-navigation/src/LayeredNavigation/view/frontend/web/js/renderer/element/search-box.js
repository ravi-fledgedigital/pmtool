define([
    "jquery",
    "domReady!"
], function ($) {
    'use strict';

    $.widget('mst.navSearchBox', {
        options: {},

        _create: function () {
            const $element = $(this.element);
            const $searchBox = $('[data-element = search]', this.element);

            $searchBox.on('change keyup', function () {
                const q = $searchBox.val().toLowerCase();

                const $items = $('[data-element = filter]', this.element);

                $items.attr('data-hidden', 'false');

                $element.trigger('showhide'); // communicate with sizeLimiter

                if (q === "") {
                    $items.attr('data-search-hidden', 'false');
                } else {
                    $items.each(function (i, item) {
                        const $item  = $(item);
                        let $label = $('label', $item);
                        
                        if ($label.length === 0) {
                            $label = $('.swatch-option', $item);
                        }
                        $label.find('*').remove(); //remove all html (count)

                        const text = $label.text().toLowerCase();

                        $item.attr('data-search-hidden', text.indexOf(q) === -1 || $item.hasClass('_disabled'));
                    });
                }

                $element.trigger('alphabetical'); // communicate with alphabetical index
                $element.trigger('nested-toggle', [q]);
            }.bind(this))
        }
    });

    return $.mst.navSearchBox;
});
