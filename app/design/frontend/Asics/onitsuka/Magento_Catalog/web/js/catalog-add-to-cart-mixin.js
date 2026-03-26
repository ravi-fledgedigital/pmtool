define([
    'jquery',
    'mage/translate',
    'underscore',
    'Magento_Catalog/js/product/view/product-ids-resolver',
    'Magento_Customer/js/customer-data',
    'domReady!'
], function ($, $t, _, idsResolver,customerData) {
    'use strict';
    return function(widget) {
        $.widget('mage.catalogAddToCart', $.mage.catalogAddToCart, {
            /** @inheritdoc */
            _create: function () {
                this._super();
                this.initMinicartOpen($("[data-block='minicart']"));

            },
            /**
             * Handler for the form 'submit' event
             *
             * @param {jQuery} form
             */
            submitForm: function (form) {
                this.ajaxSubmit(form);
                $('.mfp-close-btn-in .mfp-close').trigger('click');
                $('body').removeClass('ajax-loading').addClass('ajax-loading');
            },
            initMinicartOpen: function (minicart) {
                minicart.on('contentLoading', function () {
                    minicart.on('contentUpdated', function () {
                        minicart.find('[data-role="dropdownDialog"]').dropdownDialog("open");
                    });
                });
            },
            _redirect: function (url) {
                // customerData.set('messages', {});
                // $.cookieStorage.set('mage-messages', '');
                var urlParts, locationParts, forceReload;

                urlParts = url.split('#');
                locationParts = window.location.href.split('#');
                forceReload = urlParts[0] === locationParts[0];

                window.location.assign(url);

                if (forceReload) {
                    setTimeout(function () {
                        window.location.reload(true);
                    },4000)
                }
            },
            /**
             * @param {jQuery} form
             */
            ajaxSubmit: function (form) {
                var self = this,
                    productIds = idsResolver(form),
                    formData;
                var actions = form.attr('action');
                $(self.options.minicartSelector).trigger('contentLoading');
                self.disableAddToCartButton(form);
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

                        $(document).trigger('ajax:addToCart', {
                            'sku': form.data().productSku,
                            'productIds': productIds,
                            'form': form,
                            'response': res
                        });

                        if (self.isLoaderEnabled()) {
                            $('body').trigger(self.options.processStop);
                        }

                        if (res.backUrl) {
                            var sections = ['messages'];
                            customerData.invalidate(sections);
                            customerData.reload(sections, true);
                            self.enableAddToCartButton(form);
                            $('.loading-mask').hide();

                            if (res.customRedirectUrl) {
                                window.location.href = res.customRedirectUrl;
                            }

                            return ;
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
                        self.enableAddToCartButton(form);
                    },

                    /** @inheritdoc */
                    error: function (res) {
                        $(document).trigger('ajax:addToCart:error', {
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
        });
        return $.mage.catalogAddToCart;
    }
});
