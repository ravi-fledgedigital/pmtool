<?php

namespace Cpss\Crm\Block\Adminhtml\JobCodeGrid;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class JobCodes extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('scode', ['label' => __('Scode'), 'class' => 'required-entry']);
        $this->addColumn('scode_explaination', ['label' => __('Scode Explanation'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Scode');
    }
}
