<?php

namespace Cpss\JsBarcode\Block\Adminhtml;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Layout
 */
class Layout extends \Magento\Framework\View\Element\Template implements
    \Magento\Framework\Data\Form\Element\Renderer\RendererInterface,
    \Magento\Widget\Block\BlockInterface
{
    /**
     * Form element which re-rendering
     *
     * @var \Magento\Framework\Data\Form\Element\Fieldset
     */
    protected $element;

    /**
     * Retrieve an element
     *
     * @return \Magento\Framework\Data\Form\Element\Fieldset
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * Set an element
     *
     * @param AbstractElement $element
     * @return $this
     */
    public function setElement(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->element = $element;

        return $this;
    }

    /**
     * Render element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->element = $element;

        return $this->toHtml();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function _toHtml()
    {
        if (!$this->getTemplate()) {
            return '';
        }

        return $this->fetchView($this->getTemplateFile());
    }
}