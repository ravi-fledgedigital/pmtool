/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global setLocation:true*/
define([
    'jquery',
    'uiRegistry'
], function ($, registry) {
    'use strict';

    $.widget('mage.visualMerchandiserMassSku', {
        options: {
            massAssignButton: null,
            massAssignUrl: null,
            messagesContainer: '[data-role=messages]'
        },
        form: null,

        /**
         * @private
         */
        _create: function () {
            this.registry = registry;
            this.form = this.element.find('form');
            this._bind();
        },

        /**
         * @private
         */
        _bind: function () {
            var self = this;

            self._disableActionButtons(true);
            this.registry.get(
                'merchandiser_product_listing.merchandiser_product_listing_data_source',
                function (listingDataSource) {
                    if (!listingDataSource.firstLoad) {
                        self._disableActionButtons(false);
                    }
                    listingDataSource.on('reload', function () {
                        self._disableActionButtons(true);
                    });
                    listingDataSource.on('reloaded', function () {
                        self._disableActionButtons(false);
                    });
                }
            );
            $(this.options.massAssignButton).on('click', $.proxy(this._massAssignAction, this));
        },

        /**
         * Toggle state of action buttons.
         *
         * @param {Boolean} disabled
         * @private
         */
        _disableActionButtons: function (disabled) {
            $(this.options.massAssignButton).attr('disabled', disabled);
        },

        /**
         * @param {Event} event
         * @private
         */
        _massAssignAction: function (event) {
            var button = $(Event.element(event)),
                action = {
                    action: button.attr('role')
                };

            $.ajax({
                type: 'POST',
                data: this.form.serialize() + '&' + $.param(action),
                url: this.options.massAssignUrl,
                context: $('body'),
                showLoader: true,
                success: $.proxy(this._massActionSuccess, this)
            });
        },

        /**
         * @param {Object} response
         * @private
         */
        _massActionSuccess: function (response) {
            this._validateAjax(response);

            // jscs:disable requireCamelCaseOrUpperCaseIdentifiers
            this.element.find(this.options.messagesContainer).html(response.html_message);
            // jscs:enable requireCamelCaseOrUpperCaseIdentifiers

            this.element.trigger('requestReload', {
                'action': response.action, 'ids': response.ids
            });
        },

        /**
         * @param {Object} response
         * @private
         */
        _validateAjax: function (response) {
            if (response.ajaxExpired && response.ajaxRedirect) {
                setLocation(response.ajaxRedirect);
            } else if (response.url) {
                setLocation(response.url);
            }
        }
    });

    return $.mage.visualMerchandiserMassSku;
});
