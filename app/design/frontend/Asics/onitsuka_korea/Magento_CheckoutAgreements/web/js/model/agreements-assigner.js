/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/*global alert*/
define([
    'jquery'
], function ($) {
    'use strict';

    var agreementsConfig = window.checkoutConfig.checkoutAgreements;

    /** Override default place order action and add agreement_ids to request */
    return function (paymentData) {
        var agreementForm,
            agreementData,
            agreementIds,
            customCheckbox,
            receiveMarketingCommunications;

        if (!agreementsConfig.isEnabled) {
            return;
        }

        agreementForm = $('.payment-method._active div[data-role=checkout-agreements] input');
        customCheckbox = $(".payment-method._active input[name='checkoutAgreements']").eq(1).is(":checked");
        receiveMarketingCommunications = $(".payment-method._active .checkout-agreements input[type='checkbox']").eq(3).is(":checked");
        agreementData = agreementForm.serializeArray();
        agreementIds = [];

        agreementData.forEach(function (item) {
            agreementIds.push(item.value);
        });

        if (paymentData['extension_attributes'] === undefined) {
            paymentData['extension_attributes'] = {};
        }
        if(customCheckbox) {
            paymentData['extension_attributes']['use_personal_information'] = customCheckbox;
        }
        if(receiveMarketingCommunications && customCheckbox){
            paymentData['extension_attributes']['send_newsletter_subscription'] = true;
        }
        paymentData['extension_attributes']['agreement_ids'] = agreementIds;
    };
});