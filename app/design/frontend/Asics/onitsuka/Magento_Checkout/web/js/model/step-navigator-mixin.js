define([
        'jquery',
        'ko',
        'Magento_Customer/js/model/customer',
        'mage/translate',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/cart/totals-processor/default',
    ], function ($, ko, customer, $t, quote, totalsDefaultProvider) {
        'use strict';

        return function (target) {
            target._getCookies = function (cname) {
                var name = cname + "=";
                var decodedCookie = decodeURIComponent(document.cookie);
                var ca = decodedCookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') {
                        c = c.substring(1);
                    }
                    if (c.indexOf(name) == 0) {
                        return c.substring(name.length, c.length);
                    }
                }
                return "";
            };
            target.next = function () {
                $('body > .loading-mask').show();
                var activeIndex = 0,
                    code;
                if (typeof (event) != 'undefined') {
                    $(event.target[0]).prop('disabled', true);
                }
                target.steps().sort(this.sortItems).forEach(function (element, index) {
                    if (element.isVisible()) {
                        element.isVisible(false);
                        activeIndex = index;
                    }
                });

                if (target.steps().length > activeIndex + 1) {
                    code = target.steps()[activeIndex + 1].code;
                    target.steps()[activeIndex + 1].isVisible(true);
                    target.setHash(code);
                    document.cookie = 'checkoutStepCurrent=' + code + '; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                    document.body.scrollTop = document.documentElement.scrollTop = 0;
                    if (code == 'shipping') {
                        var cookiesValue = target._getCookies('shippingAddressData');
                        if (cookiesValue && cookiesValue != window.checkoutConfig.defaultCountryId) {
                            $('#opc-new-shipping-address .fieldset.address .field:visible').each(function () {
                                $(this).find('div.field-error').remove();
                                $(this).find('input:not(.mobile-country-code)').val('');
                            });
                        }
                        $('#opc-new-shipping-address .fieldset.address div[name="shippingAddress.country_id"] select').val(window.checkoutConfig.defaultCountryId).trigger('change');
                    }
                    if (code == 'payment') {
                        document.cookie = 'shippingAddressData=' + quote.shippingAddress()['countryId'] + '; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                    }
                }
                $('body > .loading-mask').hide();
            };
            target.navigateTo = function (code, scrollToElementId) {
                var sortedItems = target.steps().sort(target.sortItems),
                    bodyElem = $('body');

                scrollToElementId = scrollToElementId || null;

                if (!target.isProcessed(code)) {
                    return;
                }

                if (code != 'payment') {
                    totalsDefaultProvider.estimateTotals(quote.shippingAddress());
                }
                if (code == 'payment') {
                    document.cookie = 'shippingAddressData=' + quote.shippingAddress()['countryId'] + '; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                }

                sortedItems.forEach(function (element) {
                    if (element.code == code) { //eslint-disable-line eqeqeq
                        if (code == 'shipping') {
                            $('#opc-new-shipping-address .fieldset.address .field:visible').each(function () {
                                $(this).find('div.field-error').remove();
                                $(this).find('input:not(.mobile-country-code)').val('');
                            });
                        }
                        $('#' + code).find('.actions-toolbar .primary .action.continue.primary').prop('disabled', false);
                        $('#' + code).find('.actions-toolbar .primary .action.continue.primary').removeAttr('disabled');
                        element.isVisible(true);
                        bodyElem.animate({
                            scrollTop: $('#' + code).offset().top
                        }, 0, function () {
                            window.location = window.checkoutConfig.checkoutUrl + '#' + code;
                        });

                        if (scrollToElementId && $('#' + scrollToElementId).length) {
                            bodyElem.animate({
                                scrollTop: $('#' + scrollToElementId).offset().top
                            }, 0);
                        }
                    } else {
                        element.isVisible(false);
                    }

                });
                document.cookie = 'checkoutStepCurrent=' + code + '; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                if (code == 'isLogedCheck' && customer.isLoggedIn()) {
                    document.cookie = 'checkoutStepCurrent=shipping; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                }
            };
            target.registerStep = function (code, alias, title, isVisible, navigate, sortOrder) {
                var hash, active;

                if ($.inArray(code, target.validCodes) !== -1) {
                    throw new DOMException('Step code [' + code + '] already registered in step navigator');
                }

                if (alias != null) {
                    if ($.inArray(alias, target.validCodes) !== -1) {
                        throw new DOMException('Step code [' + alias + '] already registered in step navigator');
                    }
                    target.validCodes.push(alias);
                }
                target.validCodes.push(code);
                if (code == 'shipping') {
                    title = $t('Details');
                }
                if (code == 'payment') {
                    title = $t('Payments');
                }
                target.steps.push({
                    code: code,
                    alias: alias != null ? alias : code,
                    title: title,
                    isVisible: isVisible,
                    navigate: navigate,
                    sortOrder: sortOrder
                });
                active = target.getActiveItemIndex();
                target.steps.each(function (elem, index) {
                    if (active !== index) {
                        elem.isVisible(false);
                    }
                });
                target.stepCodes.push(code);
                hash = window.location.hash.replace('#', '');

                if (hash != '' && hash != code) { //eslint-disable-line eqeqeq
                    //Force hiding of not active step
                    isVisible(false);
                }
            };
            target.handleHash = function () {
                var hashString = window.location.hash.replace('#', ''), isRequestedStepVisible;
                if (hashString === '') {
                    return false;
                }
                if (hashString === 'payment') {
                    var cookiesValue = this._getCookies('shippingAddressData');
                    if (cookiesValue && cookiesValue != window.checkoutConfig.defaultCountryId) {
                        if (customer.isLoggedIn()) {
                            hashString = 'shipping';
                        } else {
                            hashString = 'isLogedCheck';
                        }
                    }
                    var clearLoadingAjax = setInterval(function () {
                        if (!$.active) {
                            $('body > .loading-mask').hide();
                                setTimeout(function(){
                                    clearInterval(clearLoadingAjax);
                            },3000)
                        }
                    });
                }
                if ($.inArray(hashString, target.validCodes) === -1) {
                    window.location.href = window.checkoutConfig.pageNotFoundUrl;
                    return false;
                }
                isRequestedStepVisible = target.steps.sort(target.sortItems).some(function (element) {
                    return (element.code == hashString || element.alias == hashString) && element.isVisible();
                });
                if (isRequestedStepVisible) {
                    return false;
                }
                target.steps().sort(target.sortItems).forEach(function (element) {
                    if (element.code == hashString || element.alias == hashString) {
                        element.isVisible(true);
                        target.navigateTo(hashString);
                        element.navigate(element);
                    } else {
                        element.isVisible(false);
                    }
                });
                return false;
            };
            return target;
        };
    }
);
