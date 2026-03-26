define([
    'Magento_ProductRecommendationsLayout/js/abstractRenderer',
    'Magento_ProductRecommendationsLayout/js/recsFetcher'
], function (abstractRenderer, recsFetcher) {
    'use strict';
    return abstractRenderer.extend({
        initialize: function (config) {
            this._super(config);
            this.recsFetcher = recsFetcher;
            this.getRecs(config);
            return this;
        },

        getRecs: function (config) {
            const options = {
                unitId: config.unitId.substring(0, 36),
                pageType: 'PageBuilder',
                defaultStoreViewCode: config.defaultStoreViewCode,
                alternateEnvironmentId: config.alternateEnvironmentId
            };

            ['storeCode', 'storeViewCode'].forEach(key => {
                if (config[key]) {options[key] = config[key];}
            });

            this.recsFetcher.fetchUnit(options).then(
                function (response) {
                    response = [response];
                    const unit = this.processResponse(response)[0];

                    if (unit !== undefined) {
                        this.recs.push(unit);
                    }
                }.bind(this)
            );
        }
    });
});
