define([
    'jquery',
    'mage/url',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/alert',
    'Magento_Customer/js/model/customer',
    'Magento_Ui/js/model/messageList',

], function($, urlBuilder, $t, customerData, alert, customer, messageList) {
    'use strict';
    return function(config) {
        $(document).ready(function() {

            $("#button-alert-pre-custom").on("click", function(e) {
                e.preventDefault();
                var btnType = 'reserve',
                    flag = false;

                var customerData = JSON.parse(localStorage['mage-cache-storage']);
                if (customerData.customer) {
                    var customer = customerData.customer;
                    if (customer.fullname && customer.firstname) {
                        flag = true;
                    }
                }
                if (flag) {
                    callAddStockNotification($(this), btnType);

                } else {
                    callAddStockNotificationForGuest($(this), btnType);
                }

            });
            $("#button-soldout-custom").on("click", function(e) {
                e.preventDefault();
            });
        });

        $('#button-alert-custom').off('click').on('click', function (e) {
            e.preventDefault();
            var selectedSize = $('.swatch-opt').find('.swatch-attribute.qa_size').find('.swatch-attribute-selected-option').html();
            $('#super_attribute_size_error').remove();
            var errorMsg = '<div id="super_attribute_size_error" class="mage-error">This is a required field.</div>';
            if (!selectedSize) {
                $('.swatch-opt').find('.swatch-attribute.qa_size').find('.swatch-input.super-attribute-select').after(errorMsg);
                return false;
            }

            var btnType = 'restock',
                flag = false;

            if (customerData.get('customer')() && customerData.get('customer')().firstname) {
                flag = true;
            }
            if (flag) {
                callAddStockNotification($(this), btnType);
            } else {
                callAddStockNotificationForGuest($(this), btnType);
            }

        });

        function callAddStockNotification($this, btnType) {
            let url = urlBuilder.build('productalert/add/stock'),
                formKey = $.mage.cookies.get('form_key'),
                selectProductId = $this.attr('data-product-id'),
                uenc = 'noredirect',
                $element = $this;
            $(document.body).trigger('processStart');
            if (selectProductId) {

                var restockType = btnType;

                $.ajax({
                    url: url,
                    dataType: "json",
                    async: false,
                    data: {
                        uenc: uenc,
                        form_key : formKey,
                        product_id: selectProductId
                    },
                    success: function(response) {
                        if (response.suceess) {
                            alert({
                                title: '',
                                responsive: true,
                                modalClass: 'restock-popup',
                                content: $.mage.__(response.message),
                                buttons: []
                            });
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        customerLoginPage(restockType, selectProductId);
                    }
                });
            } else {
                alert($t('Selected variant may not exist. Cannot register restock notice.'));
            }
            $(document.body).trigger('processStop');
        }

        function getSelectedConfigurableProductId() {
            var selected_options = {};
            jQuery('div.swatch-attribute').each(function(k, v) {
                var attribute_id = jQuery(v).attr('data-attribute-id');
                var option_selected = jQuery(v).attr('data-option-selected');
                if (!attribute_id || !option_selected) {
                    return;
                }
                selected_options[attribute_id] = option_selected;
            });

            var product_id_index = jQuery('[data-role=swatch-options]').data('mage-SwatchRenderer').options.jsonConfig.index;
            var found_ids = [];
            jQuery.each(product_id_index, function(product_id, attributes) {
                var productIsSelected = function(attributes, selected_options) {
                    return _.isEqual(attributes, selected_options);
                }
                if (productIsSelected(attributes, selected_options)) {
                    found_ids.push(product_id);
                }
            });

            return _.isArray(found_ids) && found_ids.length === 1 ? found_ids[0] : null;

        }

        function customerLoginPage(from, productId) {
            var redirectionUrl = urlBuilder.build('customer/account/login') + "/?cusRedirectionUrl=" + btoa(window.location.href) + "&autoAdd=" + btoa(from) + "&productId=" + btoa(productId);
            window.location.replace(redirectionUrl);
        }

        function callAddStockNotificationForGuest($this, btnType) {
            let url = urlBuilder.build('productalert/add/stock'),
                selectProductId = $this.attr('data-product-id'),
                formKey = $.mage.cookies.get('form_key'),
                uenc = 'redirect',
                $element = $this;
            $(document.body).trigger('processStart');
            if (selectProductId) {

                var restockType = btnType;

                $.ajax({
                    url: url,
                    dataType: "json",
                    async: false,
                    data: {
                        uenc: uenc,
                        form_key : formKey,
                        product_id: selectProductId,

                    },
                    success: function(response) {
                        if (restockType == "reserve") {
                            alert({
                                title: '',
                                responsive: true,
                                modalClass: 'restock-popup',
                                content: $.mage.__('Your reservation request has been accepted.'),
                                buttons: []
                            });

                        } else {
                            alert({
                                title: '',
                                responsive: true,
                                modalClass: 'restock-popup',
                                content: $.mage.__(response.message),
                                buttons: []
                            });
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        customerLoginPage(restockType, selectProductId);
                    }
                });
            }
            $(document.body).trigger('processStop');
        }
    }
});
