var config = {
    shim: {
        recommendationsSDK: {
            exports: "recsSDK",
        },
    },
    paths: {
        recommendationsSDK: "https://recommendations-sdk.adobe.io/v2/index",
        recommendationsEvents: ['https://commerce.adobedtm.com/recommendations/events/v1/recommendationsEvents.min', 'Magento_ProductRecommendationsLayout/js/noopRecommendationsEvents'],
    },
}
