var config = {
    map: {
        '*': {
            'amScrollToTabs': 'Amasty_CustomTabs/js/scroll-to-tabs'
        }
    },
    config: {
        mixins: {
            "Magento_Review/js/process-reviews": {
                'Amasty_CustomTabs/js/process-reviews': true
            },
            'mage/collapsible': {
                'Amasty_CustomTabs/js/collapsible-mixin': true
            },
            'mage/tabs': {
                'Amasty_CustomTabs/js/tabs-mixin': true
            }
        }
    }
};
