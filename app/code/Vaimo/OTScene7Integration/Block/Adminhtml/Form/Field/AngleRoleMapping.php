<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class AngleRoleMapping extends AbstractFieldArray
{
    use GetProductTypesRendererTrait;

    protected function _prepareToRender()
    {
        $this->addColumn('product_type', [
            'label' => __('Product type'),
            'class' => 'required-entry',
            'renderer' => $this->getProductTypesRenderer(),
        ]);

        $this->addColumn('role', [
            'label' => __('Image Role'),
            'class' => 'required-entry',
        ]);

        $this->addColumn('angle', [
            'label' => __('Angle'),
            'class' => 'required-entry',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
