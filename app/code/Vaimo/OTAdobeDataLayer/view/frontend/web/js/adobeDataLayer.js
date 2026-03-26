/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE.txt for license details.
 */

define(['jquery', 'underscore'], function ($, _) { // eslint-disable-line no-undef
    'use strict';

    return function (config) {
        let adobeDataLayer = window.adobeDataLayer ? window.adobeDataLayer : [],
            dataLayerProviderUrl = config.dataLayerProviderUrl,
            dataLayerComponents = config.dataLayerComponents;
      
        if (dataLayerProviderUrl && !_.isEmpty(dataLayerComponents)) {
            $.ajax({
                contentType: 'application/json',
                data: dataLayerComponents,
                type: 'POST',
                url: dataLayerProviderUrl
            }).done(function (response) {
                let userInfo = JSON.parse(response.user_info);
                let pageInfo = JSON.parse(response.page_info);

                var userInfoData = userInfo;
                adobeDataLayer.push(
                    {
                        'event' : 'pageView',
                        'page' : pageInfo,
                        'userInfo' : userInfoData,
                    }
                );
            });
        }
        window.adobeDataLayer = adobeDataLayer;
    };
});