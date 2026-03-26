define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry'
], function (Abstract, registry) {
    return Abstract.extend({
        initContainer: function (parent) {
            this._super();
            registry.async("index = storeviews")(function (storeViews) {
                storeViews.showHide();
            });
            return this;
        }
    })
});