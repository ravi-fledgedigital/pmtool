define(['jquery'], function($) {
    'use strict';
    return function(widget) {
        $.widget('mage.loader', $.mage.loader, {
            hide: function () {
                if (this.loaderStarted > 0) {
                    this.loaderStarted--;

                    if (this.loaderStarted === 0) {
                        this.spinner.hide();
                    }
                    if($('.checkout-index-index').length){
                        if (this.loaderStarted <= 1) {
                            this.spinner.hide();
                        }
                    }
                }

                return false;
            }
        });
        return $.mage.menu;
    }
});
