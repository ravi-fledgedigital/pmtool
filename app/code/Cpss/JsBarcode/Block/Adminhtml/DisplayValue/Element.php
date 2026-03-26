<?php

namespace Cpss\JsBarcode\Block\Adminhtml\DisplayValue;

/**
 * Class Element
 */
class Element extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Retrieve element HTML markup
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $renderer = $this->getLayout()->createBlock(
            'Cpss\JsBarcode\Block\Adminhtml\DisplayValue\Layout'
        );
        $renderer->setElement($element);

        return $renderer->toHtml();
    }
}