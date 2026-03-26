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
                type: 'portonesamsungpay',
                component: 'OnitsukaTiger_PortOne/js/view/payment/method-renderer/portone-samsungpay'
            }
        );
        return Component.extend({});
    }
);
