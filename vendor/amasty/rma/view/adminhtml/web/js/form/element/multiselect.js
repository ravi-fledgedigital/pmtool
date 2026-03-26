define([
    'Magento_Ui/js/form/element/multiselect',
    'underscore',
    'uiRegistry'
], function (Multiselect, _, registry) {
    'use strict';

    return Multiselect.extend({
        lastValue: null,
        showHide: function () {
            var value = this.value();
            if (this.lastValue) {
                _.each(_.difference(this.lastValue, value), function (hideStoreId) {
                    registry.async("index = storelabel" + hideStoreId)(function (el) {
                        el.visible(false);
                    });
                });
                _.each(_.difference(value, this.lastValue), function (showStoreId) {
                    registry.async("index = storelabel" + showStoreId)(function (el) {
                        el.visible(true);
                    });
                });
            } else if (!_.isEmpty(value)) {
                _.each(value, function (storeId) {
                    registry.async("index = storelabel" + storeId)(function (el) {
                        el.visible(true);
                    });
                })
            }

            //hide fieldsets where labels are hidden
            registry.async("index = labels")(function (labelsContainer) {
                _.each(labelsContainer.elems(), function (website) {
                    if (website.index !== 'store0') {
                        var hideWebsite = true;
                        _.each(website.elems(), function (storeGroup) {
                            var hideGroup = true;
                            _.each(storeGroup.elems(), function (storeView) {
                                var hideStoreView = true;
                                _.each(storeView.elems(), function (elem) {
                                    if (elem.visible()) {
                                        hideStoreView = false;
                                    }
                                });
                                if (hideStoreView) {
                                    storeView.visible(false);
                                } else {
                                    storeView.visible(true);
                                    hideGroup = false;
                                }
                            });
                            if (!hideGroup) {
                                hideWebsite = false;
                                storeGroup.visible(true);
                            } else {
                                storeGroup.visible(false);
                            }
                        });
                        if (hideWebsite) {
                            website.visible(false);
                        } else {
                            website.visible(true);
                        }
                    } else {
                        registry.async("index = storelabel0")(function (allStoreViews) {
                            allStoreViews.containers[0].visible(allStoreViews.visible());
                        });
                    }
                })
            });

            this.lastValue = value;
        },
        onUpdate: function () {
            this._super();
            this.showHide()
        }
    });
});
