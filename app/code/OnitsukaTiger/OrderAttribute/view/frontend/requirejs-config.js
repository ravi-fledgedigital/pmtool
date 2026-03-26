var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-shipping-information': {
                'OnitsukaTiger_OrderAttribute/js/action/set-shipping-information-mixin': true
            },
            'Magento_Checkout/js/action/place-order': {
                'OnitsukaTiger_OrderAttribute/js/action/place-order-mixin': true
            },
            'Amazon_Payment/js/action/place-order': {
                'OnitsukaTiger_OrderAttribute/js/action/place-order-mixin': true
            },
            'Magento_Paypal/js/action/set-payment-method': {
                'OnitsukaTiger_OrderAttribute/js/action/set-payment-method-mixin': true
            },
            'Magento_Checkout/js/action/set-payment-information': {
                'OnitsukaTiger_OrderAttribute/js/action/set-payment-information-mixin': true
            }
        }
    }
};
