var config = {
    map:    {
        '*': {
            productListToolbarForm: 'Mirasvit_LayeredNavigation/js/ajax/toolbar'
        }
    },
    config: {
        mixins: {
            'mage/collapsible': {
                'Mirasvit_LayeredNavigation/js/collapsible-extended': true
            },
            'mage/accordion': {
                'Mirasvit_LayeredNavigation/js/accordion-extended': true
            },
            'Magento_Swatches/js/swatch-renderer': {
                'Mirasvit_LayeredNavigation/js/swatch-renderer-mixin': true
            }
        }
    }
};
