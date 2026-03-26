define([
    'jquery',
    'Mirasvit_LayeredNavigation/js/config',
    'Mirasvit_LayeredNavigation/js/action/apply-filter'
], function ($, config, applyFilter) {
    'use strict';

    /**
     * Work with default filters.
     */
    $.widget('mst.navLabelRenderer', {
        _create: function () {
            $('[data-element = filter]', this.element).each(function (idx, item) {
                const $item = $(item);

                $item.on('click', function (e) {

                    const url = $item.prop('tagName') === 'A'
                        ?   $item.prop('href')
                        : $('a', $item).prop('href');

                    if (config.mstCategoryFilterModeLink() && $item.parents('.mst-nav__category').length) {
                        window.location.href = url
                        return;
                    }

                    if ($(e.target).prop('tagName') !== 'INPUT') {
                        e.preventDefault();
                        e.stopPropagation();
                    } 

                    const $target = $item.hasClass('mst-nav__label-item')
                        ? $item
                        : $item.parent('.mst-nav__label-item');

                    if ($target.length && $target.hasClass('_disabled')) {
                        return;
                    }

                    if ($item.prop('tagName') !== 'A') {
                        if ($(e.target).prop('tagName') !== 'INPUT') {
                            this.toggleCheckbox($item);
                        } else {
                            // firefox fix
                            if (
                                e.target.hasAttribute('checked')
                                && e.target.getAttribute('checked') !== "false"
                            ) {
                                e.target.setAttribute('checked', true);
                            }

                            e.target.setAttribute(
                                'checked',
                                e.target.getAttribute('checked') == 'true' ? 'false' : 'true'
                            );
                        }
                    }

                    this.toggleSwatch($item);
                    this.showHighLight($item);

                    applyFilter.apply(url, $item);
                }.bind(this))
            }.bind(this));
        },

        showHighLight: function ($el) {
            if ($el.hasClass('_checked')) {
                $el.addClass("_highlight");
            } else {
                $el.removeClass("_highlight");
            }
        },

        toggleCheckbox: function ($el) {
            const $checkbox = $('input[type=checkbox]', $el);

            if ($checkbox.prop('disabled')){
                return true;
            }

            $checkbox.prop('checked', !$checkbox.prop('checked')).attr('checked', $checkbox.prop('checked'));
        },

        toggleSwatch: function ($el) {
            const $checkbox = $('input[type=checkbox]', $el);

            if ($checkbox.prop('disabled')){
                return true;
            }

            if ($el.hasClass('_checked')) {
                $el.removeClass('_checked');
            } else {
                $el.addClass('_checked');
            }
        }
    });

    return $.mst.navLabelRenderer;
});
