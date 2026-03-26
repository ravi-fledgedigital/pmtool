/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            addressValidationCharacter: 'Magento_Customer/js/address-character'
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/lib/validation/validator': {
                'Magento_Customer/js/validation-rule-mixin': true
            },
            'mage/validation': {
                'Magento_Customer/js/validation-mixin': true
            }
        }
    }
};
