define(
    ['Magento_PageBuilder/js/utils/object'],
    function (objectUtil)
    {
        'use strict';
        function Converter() {}
        Converter.prototype = {
        // from master.html
            fromDom: function (data, config) {
                let htmlVariableText = objectUtil
                        .get(data, config.html_variable),
                    recId = htmlVariableText
                        .match(/unit_id="(.*?)"/)[1],
                    storeId = htmlVariableText
                        .match(/store_id="(.*?)"/) !== null ?
                        htmlVariableText
                            .match(/store_id="(.*?)"/)[1] : '';

                objectUtil.set(data, 'unit_id', recId);
                objectUtil.set(data, 'store_id', storeId);
                return data;
            },
            // to master.html
            toDom: function (data, config) {
                let htmlVariableText =
                // eslint-disable-next-line max-len
                '{{block class="Magento\\PageBuilderProductRecommendations\\Block\\PageBuilderRecommendation" unit_id="' +
                data['unit_id'] + '" store_id="' + data['store_id'] +
                '"}}';

                objectUtil.set(data, config.html_variable, htmlVariableText);
                return data;
            }
        };
        return Converter;
    });
