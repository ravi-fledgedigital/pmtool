<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace OnitsukaTiger\Cegid\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class SourceMapping extends AbstractFieldArray
{
    use GetSourceTypesRendererTrait;

    /**
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn('source_code', [
            'label' => __('Source Code'),
            'class' => 'required-entry',
            'renderer' => $this->getProductTypesRenderer(),
        ]);

        $this->addColumn('store', [
            'label' => __('Cegid Store Code'),
            'class' => 'required-entry',
        ]);

        $this->addColumn('wh_code', [
            'label' => __('Cegid Warehouse Code'),
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
