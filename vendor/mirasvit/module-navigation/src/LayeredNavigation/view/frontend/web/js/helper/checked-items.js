define([
    'jquery',
    'Mirasvit_LayeredNavigation/js/action/apply-filter',
    'Mirasvit_LayeredNavigation/js/config',
], function ($, applyFilter, config) {
    'use strict';

    $.widget('mst.navHelperCheckedItems', {
        options: {
            count: 0,
            clearUrl: ''
        },

        _create: function () {
            if (!this.options.count || !config.isShowClearButton()) {
                return;
            }

            const counterWrapper = $('<div/>').addClass('mst-nav__checked-counter__wrapper');

            const counter = $('<div/>').addClass('mst-nav__checked-counter').text(this.options.count);
            const clearLink = $('<a/>');
            clearLink.attr('href', this.options.clearUrl);
            clearLink.attr('rel', config.getRelAttributeValue);
            clearLink.attr('data-element', 'filter');
            clearLink.html('&#10005;');

            // hide counter and clear button in precalculation mode
            counter.hide();
            clearLink.hide();
            clearLink.on('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                applyFilter.apply(this.options.clearUrl);
            }.bind(this))

            counterWrapper.append(counter).append(clearLink);

            const $title = $(this.element).closest('.filter-options-item').find('.filter-options-title');

            if ($('.mst-nav__checked-counter__wrapper', $title).length) {
                $('.mst-nav__checked-counter__wrapper', $title).remove();
            }
            $title.append(counterWrapper);

            // show counter and clear button if there is no precalculation mode
            setTimeout(() => {
                if (!($('.mst-nav__apply-button-wrapper').is(":visible") && config.isAjax() && !config.isInstantMode())) {
                    counter.show();
                    clearLink.show();
                }
            }, 200);
        },
    });

    return $.mst.navHelperCheckedItems;
});
