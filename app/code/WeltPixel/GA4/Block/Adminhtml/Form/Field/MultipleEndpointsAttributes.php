<?php
namespace WeltPixel\GA4\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

Class MultipleEndpointsAttributes extends AbstractFieldArray
{

    /**
     * @var \WeltPixel\GA4\Block\Adminhtml\Form\Field\View\Element\Textarea
     */
    protected $_textareaRenderer;

    protected function _prepareToRender()
    {
        $this->addColumn('measurement_id', [
            'label' => __('Measurement ID'),
            'size' => 100,
            'class' => 'required-entry admin__control-text',
            'renderer' => $this->_getTextareaRender()
        ]);
        $this->addColumn('api_secret', [
            'label' => __('API Secret'),
            'size' => 100,
            'class' => 'required-entry admin__control-text',
            'renderer' => $this->_getTextareaRender()
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Property');
    }


    protected function _getTextareaRender()
    {
        if (!$this->_textareaRenderer) {
            $this->_textareaRenderer = $this->getLayout()->createBlock(
                \WeltPixel\GA4\Block\Adminhtml\Form\Field\View\Element\Textarea::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            )->setExtraParams('rows="1"');
            $this->_textareaRenderer->setClass('required-entry admin__control-text');
        }

        return $this->_textareaRenderer;
    }
}
