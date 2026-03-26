var config = {
    map: {
        '*': {
            'Magento_Checkout/template/shipping-address/address-renderer/default.html':
                'OnitsukaTigerKorea_Checkout/template/shipping-address/address-renderer/default.html',
            'hangulCharactersValidate': "OnitsukaTigerKorea_Checkout/js/xml-hangul-validate"
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/lib/validation/validator': {
                'OnitsukaTigerKorea_Checkout/js/validator-mixin': true
            },
            'Magento_Checkout/js/action/place-order': {
                'OnitsukaTigerKorea_Checkout/js/order/place-order-mixin': true
            },
            'Magento_Checkout/js/view/payment': {
                'OnitsukaTigerKorea_Checkout/js/view/payment-mixin': true
            },
            'Magento_Checkout/js/model/shipping-save-processor/payload-extender': {
                'OnitsukaTigerKorea_Checkout/js/mixin/shipping-payload-extender-mixin': true
            }
        }
    }
};
