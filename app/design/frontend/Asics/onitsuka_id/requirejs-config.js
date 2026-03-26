var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/progress-bar': {
                'Magento_Checkout/js/view/progress-bar-mixin': true
            },
            'Magento_Checkout/js/action/set-shipping-information': {
                'Magento_Checkout/js/model/set-shipping-information-mixin': true
            },
            'Magento_Checkout/js/action/select-shipping-method': {
                'Magento_Checkout/js/action/select-shipping-method-mixin': true
            },
            'Magento_Checkout/js/view/cart/shipping-rates': {
                'Magento_Checkout/js/view/cart/shipping-rates-mixin': true
            }
        }
    },
    map: {
        "*": {
            citiesUpdater: 'Magento_Customer/js/cities-updater',
            districtUpdater: 'Magento_Customer/js/district-updater'
        }
    }
};
