define([
    'jquery',
    'underscore',
    'mage/storage',
    'OnitsukaTiger_OrderAttribute/js/action/onitsukatiger-validate-form',
    'OnitsukaTiger_OrderAttribute/js/action/onitsukatiger-prepare-attributes-for-api',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/url-builder',
    'mage/url'
], function ($, _, storage, validateForm, prepareAttributesForApi, customer, fullScreenLoader, errorProcessor, urlBuilder, mageUrl) {
    'use strict';

    var sentDataSnapshot = false;

    return function (attributesForm) {
        var waitForOrderAttributesResult = $.Deferred(),
            result = validateForm(attributesForm.attributeTypes);

        if (!result) {

            return waitForOrderAttributesResult.reject();
        } else {
            var postData = prepareAttributesForApi(result, attributesForm.formCode);

            if ((sentDataSnapshot && _.isEqual(postData.entityData, sentDataSnapshot.entityData))
                || _.isEmpty(postData.entityData.custom_attributes)
            ) {
                return waitForOrderAttributesResult.resolve();
            }

            sentDataSnapshot = postData;
            fullScreenLoader.startLoader();

            var xhr = new XMLHttpRequest(),
                link = mageUrl.build(
                    urlBuilder.createUrl(
                        customer.isLoggedIn()
                                ? '/onitsukatiger_orderattribute/checkoutData'
                                : '/onitsukatiger_orderattribute/guestCheckoutData',
                        {}
                    )
                );
            xhr.open("POST", link, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function () {
                if (4 !== parseInt(this.readyState)) {
                    return;
                }
                if (200 !== parseInt(this.status)) {
                    errorProcessor.process(this.responseText);
                    waitForOrderAttributesResult.resolve();
                    fullScreenLoader.stopLoader();
                    return;
                }
                var response = JSON.parse(this.responseText);
                if (!_.isUndefined(response.errors)) {
                    console.error(response.errors)
                }
                waitForOrderAttributesResult.resolve();
                fullScreenLoader.stopLoader();
            };
            xhr.send(JSON.stringify(postData));

            return waitForOrderAttributesResult.promise();
        }
    }
});