define(['jquery'], function($) {
    'use strict';
    return function(targetWidget) {
        $.widget('ui.menu', targetWidget, {
            /**
             * @private
             */
            _create: function () {
                if(this.element.prop("tagName") !='BODY') {
                    this._super();
                } else{
                    return;
                }
            }
        });
    }
});
