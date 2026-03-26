/**
 * Mage tabs mixin
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        $.widget('mage.tabs', widget, {
            options: {
                accordionOpenViewClass: 'amtabs-open'
            },

            /**
             * @private
             * @inheritDoc
             *
             * @return {void}
             */
            _create: function () {
                this.isAccordion = this.element.hasClass(this.options.accordionOpenViewClass);

                if (this.isAccordion) {
                    this.options.collapsible = true;
                }

                this._super();
            },

            /**
             * @private
             *
             * @return {void}
             */
            _callCollapsible: function () {
                var self = this,
                    disabled = false,
                    active = false;

                if (!this.isAccordion) {
                    this._super();

                    return;
                }

                $.each(this.collapsibles, function (i) {
                    disabled = active = false;
                    active = true;

                    self._instantiateCollapsible(this, i, active, disabled);
                });
            },

            _handleDeepLinking: function () {
                if (!this.isAccordion) {
                    this._super();
                }
            },

            /**
             * @private
             *
             * @return {void}
             */
            _closeOthers: function () {
                if (!this.isAccordion) {
                    this._super();

                    return;
                }

                $.each(this.collapsibles, function () {
                    $(this).on('beforeOpen', function () {
                        var section = $(this);

                        section.addClass('allow').prevAll().addClass('allow');
                        section.nextAll().removeClass('allow');
                    });
                });
            }
        });

        return $.mage.tabs;
    };
});
