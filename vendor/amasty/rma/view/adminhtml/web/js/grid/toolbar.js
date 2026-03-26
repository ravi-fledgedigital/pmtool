define([
    'Magento_Ui/js/grid/toolbar',
    'underscore',
    'jquery'
], function (toolbar, _, $) {
    return toolbar.extend({
        show: function () {
            if ($('.page-main-actions').length === 0) {
                this.$sticky.style.top = 0;
            }

            return this._super();
        }
    })
});
