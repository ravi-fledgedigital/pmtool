var config = {
    config: {
        mixins: {
            'Magento_Wishlist/js/view/wishlist': {
                'Magento_Wishlist/js/view/wishlist-mixin': true
            },
            'Magento_Theme/js/view/messages': {
                'Magento_Theme/js/view/messages-mixin': true
            },
            'Magento_Tax/js/view/checkout/summary/shipping': {
                'Magento_Tax/js/view/checkout/summary/shipping-mixin': true
            },
            'mage/loader': {
                'Magento_Theme/js/loader-mixin': true
            },
            'Magento_Checkout/js/action/get-payment-information': {
                'Magento_Checkout/js/action/get-payment-information-mixin': true
            },
            'jquery/ui-modules/widgets/menu': {
                'Magento_Theme/js/widgets/menu-mixin': true
            },
            'Magento_Tax/js/view/checkout/summary/grand-total': {
                'Magento_Tax/js/view/checkout/summary/grand-total-mixin': true
            },
            'Magento_Checkout/js/view/progress-bar': {
                'Magento_Checkout/js/view/progress-bar-mixin' : true
            }
        }
    }
};
