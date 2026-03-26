define(['jquery'], function($) {
    'use strict';
    return function(widget) {
        $.widget('mage.collapsible', widget, {
            _scrollToTopIfVisible: function(elem) {
                if (typeof ($(elem).data('disableScroll')) == 'undefined') {
                    if (this._isElementOutOfViewport(elem)) {
                        if ($(window).width() > 768) {
                            $('html, body').animate({
                                scrollTop: $(elem).offset().top - 150
                            });
                        } else {
                            $('html, body').animate({
                                scrollTop: $(elem).offset().top
                            });
                        }
                    }
                }
            },
            _scrollToTopIfNotVisible: function() {
                if (typeof ($(this.header[0]).data('disableScroll')) == 'undefined') {
                    if (this._isElementOutOfViewport()) {
                        if ($(window).width() > 768) {
                            $('html, body').animate({
                                scrollTop: $(this.header[0]).offset().top - 150
                            });
                        } else {
                            this.header[0].scrollIntoView();
                        }
                    }
                }
            }
        });
        return $.mage.validation;
    }
});
