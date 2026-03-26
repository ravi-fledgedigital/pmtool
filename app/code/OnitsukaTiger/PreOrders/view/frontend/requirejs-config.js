var config = {
    config: {
        mixins: {
            'Magento_Swatches/js/swatch-renderer': {
                'OnitsukaTiger_PreOrders/js/mixins/swatches/swatch-renderer': true
            },
            'Magento_ConfigurableProduct/js/configurable': {
                'OnitsukaTiger_PreOrders/js/mixins/configurable-product/configurable': true
            },
            'Magento_Catalog/js/catalog-add-to-cart': {
                'OnitsukaTiger_PreOrders/js/mixins/catalog/catalog-add-to-cart': true
            }
        }
    },
    map: {
        '*': {
            ot_preorders_product: 'OnitsukaTiger_PreOrders/js/product'
        }
    }
};