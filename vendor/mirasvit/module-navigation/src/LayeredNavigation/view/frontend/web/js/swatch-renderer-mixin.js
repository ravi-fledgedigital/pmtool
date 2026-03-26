define([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';

    return function (SwatchRenderer) {
        $.widget('mage.SwatchRenderer', SwatchRenderer, {
            /**
             * Override _RenderControls to use resolved group options for preselection.
             *
             * @private
             */
            _RenderControls: function () {
                this._super();

                // After rendering, apply resolved group options for preselection
                this._EmulateResolvedGroupOptions();
            },

            /**
             * Preselect swatches based on resolved grouped options.
             *
             * When a grouped option filter is applied, the PHP plugin resolves
             * the group code to a specific option ID for each product.
             * This method applies that preselection.
             *
             * @private
             */
            _EmulateResolvedGroupOptions: function () {
                var resolvedOptions = this.options.jsonConfig.resolvedGroupOptions;

                if (!resolvedOptions || _.isEmpty(resolvedOptions)) {
                    return;
                }

                $.each(resolvedOptions, $.proxy(function (attributeCode, optionId) {
                    var elem = this.element.find('.' + this.options.classes.attributeClass +
                            '[data-attribute-code="' + attributeCode + '"] [data-option-id="' + optionId + '"]'),
                        parentInput;

                    if (elem.length === 0 || elem.hasClass('selected')) {
                        return;
                    }

                    parentInput = elem.parent();

                    if (parentInput.hasClass(this.options.classes.selectClass)) {
                        parentInput.val(optionId);
                        parentInput.trigger('change');
                    } else {
                        elem.trigger('click');
                    }
                }, this));
            }
        });

        return $.mage.SwatchRenderer;
    };
});
