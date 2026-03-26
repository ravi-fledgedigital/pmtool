define([
    'jquery',
    'Mirasvit_LayeredNavigation/js/config'
], function ($, config) {
    'use strict';

    return function (applyButton) {
        function updateApplyButtonVisibility() {
            const $pending = $('.mst-nav__label-item._pending._checked, .swatch-option-link-layered._pending._checked');
            if ($pending.length === 0) {
                applyButton.hide();
            } else if (!config.isInstantMode()) {
                applyButton.show();
            }
        }

        $(document).off('change.applyButtonInteractions click.applyButtonInteractions mst-nav-filter-toggle.applyButtonInteractions');

        $(document).on('change.applyButtonInteractions', '.mst-nav__label-item input[type="checkbox"], .mst-nav__label-item input[type="radio"]', function () {
            $(this).closest('.mst-nav__label-item').addClass('_pending');
            updateApplyButtonVisibility();
        });

        $('.mst-nav__label-item a').on('click.applyButtonInteractions', function (e) {
            e.preventDefault();
            $(this).closest('.mst-nav__label-item').addClass('_pending');
            requestAnimationFrame(() => {
                updateApplyButtonVisibility();
            });
        });

        $('.mst-nav__swatch a.swatch-option-link-layered').on('click.applyButtonInteractions', function (e) {
            e.preventDefault();
            $(this).addClass('_pending');
            requestAnimationFrame(() => {
                updateApplyButtonVisibility();
            });
        });

        $(document).on('mst-nav-filter-toggle.applyButtonInteractions', function () {
            requestAnimationFrame(() => {
                const openedFilters = $('.filter-options-item.active');
                const selectedItems = $('.filter-options-item.active .mst-nav__label-item._checked._pending, .filter-options-item.active .swatch-option-link-layered._checked._pending');

                if (openedFilters.length === 0 || selectedItems.length === 0) {
                    applyButton.hide();
                    return;
                }

                const $firstSelected = selectedItems.first();
                if ($firstSelected.length) {
                    applyButton.move($firstSelected);
                    applyButton.show();
                }
            });
        });
    };
});
