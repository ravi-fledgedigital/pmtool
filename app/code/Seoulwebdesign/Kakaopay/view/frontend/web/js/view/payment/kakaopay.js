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
                type: 'kakaopay',
                component: 'Seoulwebdesign_Kakaopay/js/view/payment/method-renderer/kakaopay-method'
            }
        );
        return Component.extend({});
    }
);