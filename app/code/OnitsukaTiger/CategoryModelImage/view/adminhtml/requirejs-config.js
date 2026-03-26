/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    config: {
        mixins: {
            'Magento_Ui/js/modal/modal': {
                'OnitsukaTiger_CategoryModelImage/js/modal/modal-mixin': true
            }
        }
    },
    map: {
        '*': {
            categoriesGallery:  'OnitsukaTiger_CategoryModelImage/js/product-gallery',
        }
    }
};
