define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'portone_npay',
                component: 'OnitsukaTiger_PortOne/js/view/payment/method-renderer/portone-npay'
            }
        );
        return Component.extend({});
    }
);