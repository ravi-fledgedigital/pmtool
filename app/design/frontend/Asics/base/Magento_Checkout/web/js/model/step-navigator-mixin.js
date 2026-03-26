define([
        'jquery',
        'ko',
        'Magento_Customer/js/model/customer',
        'mage/translate',
    ], function ($, ko, customer, $t) {
        'use strict';

        return function (target) {
            target.next = function () {
                var activeIndex = 0,
                    code;

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
                    document.cookie = 'checkoutStepCurrent='+code+'; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                    document.body.scrollTop = document.documentElement.scrollTop = 0;
                }
            };
            target.navigateTo = function (code, scrollToElementId) {
                var sortedItems = target.steps().sort(target.sortItems),
                    bodyElem = $('body');

                scrollToElementId = scrollToElementId || null;

                if (!target.isProcessed(code)) {
                    return;
                }
                sortedItems.forEach(function (element) {
                    if (element.code == code) { //eslint-disable-line eqeqeq
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
                document.cookie = 'checkoutStepCurrent='+code+'; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                if(code =='isLogedCheck' && customer.isLoggedIn()){
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
                if(code == 'shipping'){
                    title = $t('Details');
                }
                if(code == 'payment'){
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
            return target;
        };
    }
);
