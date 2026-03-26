<?php
namespace WeltPixel\GA4\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

Class ScriptAttributes extends AbstractFieldArray
{

    protected function _prepareToRender()
    {
        $this->addColumn('script_attribute', ['label' => __('Script Attribute'), 'class' => 'required-entry admin__control-text']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Attribute');
    }
}
