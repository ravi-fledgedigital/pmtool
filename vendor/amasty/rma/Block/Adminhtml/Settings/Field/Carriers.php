<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml\Settings\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class Carriers extends AbstractFieldArray
{
    protected function _prepareToRender()
    {
        $this->addColumn(
            'carrier_code',
            ['label' => __('Carrier Code'), 'class' => 'required-entry']
        );
        $this->addColumn(
            'carrier_label',
            ['label' => __('Carrier Label'), 'class' => 'required-entry']
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
