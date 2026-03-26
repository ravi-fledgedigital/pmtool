define([
    'jquery',
    'Mirasvit_LayeredNavigation/js/config',
], function ($, config) {
    'use strict';

    // mage/collapsible
    return function (widget) {
        $.widget('mage.accordion', widget, {
            
            _closeOthers: function () {

                const applyButton = document.querySelector('[data-element="mst-nav__applyButton"]');

                if (applyButton && config.preCalculationEnabled()) {
                    return;
                }

                return this._super();
            }

        });
        return $.mage.accordion;
    }
});