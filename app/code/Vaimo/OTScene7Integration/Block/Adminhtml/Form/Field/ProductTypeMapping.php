<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class ProductTypeMapping extends AbstractFieldArray
{
    use GetProductTypesRendererTrait;

    protected function _prepareToRender()
    {
        $this->addColumn('product_type', [
            'label' => __('Product type'),
            'class' => 'required-entry',
            'renderer' => $this->getProductTypesRenderer(),
        ]);

        $this->addColumn('asset_angle', [
            'label' => __('Asset\Angle'),
            'class' => 'required-entry',
        ]);

        $this->addColumn('priority', [
            'label' => __('Priority'),
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
