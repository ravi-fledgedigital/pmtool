/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
        'jquery'

    ], function ($) {
        'use strict';

        return function (Class) {
            return Class.extend({
                /**
                 * Extracts data which matches specified parameters.
                 *
                 * @param {Object} params - Request parameters.
                 * @param {Object} [options={}]
                 * @returns {jQueryPromise}
                 */
                getData: function (params, options) {
                    var cachedRequest;

                    if (this.hasScopeChanged(params)) {
                        this.clearRequests();
                    } else {
                        if(params.namespace != 'restock_listing' && params.namespace != 'onitsuka_favorite_listing'){
                            cachedRequest = this.getRequest(params);
                        }
                    }

                    options = options || {};

                    return !options.refresh && cachedRequest ?
                        this.getRequestData(cachedRequest) :
                        this.requestData(params);
                },
            });
        }

    });
