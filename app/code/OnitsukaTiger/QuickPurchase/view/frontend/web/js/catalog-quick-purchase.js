/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'underscore',
    'Magento_Catalog/js/product/view/product-ids-resolver',
    'jquery-ui-modules/widget',
    'domReady!'
], function ($, $t, _, idsResolver) {
    'use strict';

    $.widget('mage.catalogQuickPurchase', {
        options: {
            processStart: null,
            processStop: null,
            bindSubmit: true,
            minicartSelector: '[data-block="minicart"]',
            messagesSelector: '[data-placeholder="messages"]',
            productStatusSelector: '.stock.available',
            quickPurchaseButtonSelector: '.action.quick-purchase',
            quickPurchaseButtonDisabledClass: 'disabled',
            quickPurchaseButtonTextWhileAdding: '',
            quickPurchaseButtonTextAdded: '',
            quickPurchaseButtonTextDefault: ''
        },

        /** @inheritdoc */
        _create: function () {
            if (this.options.bindSubmit) {
                this._bindSubmit();
            }
        },

        /**
         * @private
         */
        _bindSubmit: function () {
            var self = this;

            if (this.element.data('catalog-quickpurchase-initialized')) {
                return;
            }

            this.element.data('catalog-quickpurchase-initialized', 1);
            this.element.on('submit', function (e) {
                e.preventDefault();
                self.submitForm($(this));
            });
        },

        /**
         * @private
         */
        _redirect: function (url) {
            var urlParts, locationParts, forceReload;

            urlParts = url.split('#');
            locationParts = window.location.href.split('#');
            forceReload = urlParts[0] === locationParts[0];

            window.location.assign(url);

            if (forceReload) {
                window.location.reload();
            }
        },

        /**
         * @return {Boolean}
         */
        isLoaderEnabled: function () {
            return this.options.processStart && this.options.processStop;
        },

        /**
         * Handler for the form 'submit' event
         *
         * @param {jQuery} form
         */
        submitForm: function (form) {
            this.ajaxSubmit(form);
        },

        /**
         * @param {jQuery} form
         */
        ajaxSubmit: function (form) {
            if(form.valid()) {
                $('.mfp-close-btn-in .mfp-close').trigger('click');
                $('body').removeClass('ajax-loading').addClass('ajax-loading');
                var self = this,
                    productIds = idsResolver(form),
                    formData;
                $(self.options.minicartSelector).trigger('contentLoading');
                self.disableQuickPurchaseButton(form);
                formData = new FormData(form[0]);

                $.ajax({
                    url: form.attr('action'),
                    data: formData,
                    type: 'post',
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,

                    /** @inheritdoc */
                    beforeSend: function () {
                        if (self.isLoaderEnabled()) {
                            $('body').trigger(self.options.processStart);
                        }
                    },

                    /** @inheritdoc */
                    success: function (res) {
                        var eventData, parameters;

                        $(document).trigger('ajax:quickPurchase', {
                            'sku': form.data().productSku,
                            'productIds': productIds,
                            'form': form,
                            'response': res
                        });

                        if (self.isLoaderEnabled()) {
                            $('body').trigger(self.options.processStop);
                        }

                        if (res.backUrl) {
                            eventData = {
                                'form': form,
                                'redirectParameters': []
                            };
                            // trigger global event, so other modules will be able add parameters to redirect url
                            $('body').trigger('catalogCategoryQuickPurchaseRedirect', eventData);

                            self._redirect(res.backUrl);

                            return;
                        }

                        if (res.messages) {
                            $(self.options.messagesSelector).html(res.messages);
                        }

                        if (res.minicart) {
                            $(self.options.minicartSelector).replaceWith(res.minicart);
                            $(self.options.minicartSelector).trigger('contentUpdated');
                        }

                        if (res.product && res.product.statusText) {
                            $(self.options.productStatusSelector)
                                .removeClass('available')
                                .addClass('unavailable')
                                .find('span')
                                .html(res.product.statusText);
                        }
                        self.enableQuickPurchaseButton(form);
                    },

                    /** @inheritdoc */
                    error: function (res) {
                        $(document).trigger('ajax:quickPurchase:error', {
                            'sku': form.data().productSku,
                            'productIds': productIds,
                            'form': form,
                            'response': res
                        });
                    },

                    /** @inheritdoc */
                    complete: function (res) {
                        if (res.state() === 'rejected') {
                            location.reload();
                        }
                    }
                });
            }
        },

        /**
         * @param {String} form
         */
        disableQuickPurchaseButton: function (form) {
            var quickPurchaseButtonTextWhileAdding = this.options.quickPurchaseButtonTextWhileAdding || $t('Purchasing...'),
                quickPurchaseButton = $(form).find(this.options.quickPurchaseButtonSelector);

            quickPurchaseButton.addClass(this.options.quickPurchaseButtonDisabledClass);
            quickPurchaseButton.find('span').text(quickPurchaseButtonTextWhileAdding);
            quickPurchaseButton.attr('title', quickPurchaseButtonTextWhileAdding);
        },

        /**
         * @param {String} form
         */
        enableQuickPurchaseButton: function (form) {
            var quickPurchaseButtonTextAdded = this.options.quickPurchaseButtonTextAdded || $t('Purchasing...'),
                self = this,
                quickPurchaseButton = $(form).find(this.options.quickPurchaseButtonSelector);

            quickPurchaseButton.find('span').text(quickPurchaseButtonTextAdded);
            quickPurchaseButton.attr('title', quickPurchaseButtonTextAdded);

            setTimeout(function () {
                var quickPurchaseButtonTextDefault = self.options.quickPurchaseButtonTextDefault || $t('Quick Purchase');

                quickPurchaseButton.removeClass(self.options.quickPurchaseButtonDisabledClass);
                quickPurchaseButton.find('span').text(quickPurchaseButtonTextDefault);
                quickPurchaseButton.attr('title', quickPurchaseButtonTextDefault);
            }, 1000);
        }
    });

    return $.mage.catalogQuickPurchase;
});
