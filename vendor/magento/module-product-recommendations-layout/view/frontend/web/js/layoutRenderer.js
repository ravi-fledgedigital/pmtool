define([
    "Magento_ProductRecommendationsLayout/js/abstractRenderer",
    "Magento_ProductRecommendationsLayout/js/recsFetcher",
    "dataServicesBase",
    "jquery",
], function (abstractRenderer, recsFetcher, ds, $) {
    "use strict"
    return abstractRenderer.extend({
        initialize: function (config) {
            this._super(config)
            this.recsFetcher = recsFetcher
            this.getRecs()
            return this
        },

        getRecs: async function () {
            this.recsFetcher.fetchPagePreconfigured().then(
                function (response) {
                    var units = this.processResponse(response)
                    // check if it is for this pagePlacement
                    units = units.filter(
                        unit =>
                            unit.pagePlacement === this.pagePlacement &&
                            unit.products.length > 0,
                    )
                    units.forEach(unit => {
                        this.recs.push(unit)
                    })
                }.bind(this),
            )
        },
    })
})
