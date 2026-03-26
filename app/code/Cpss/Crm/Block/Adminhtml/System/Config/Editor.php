<?php
namespace Cpss\Crm\Block\Adminhtml\System\Config;
 
use Magento\Backend\Block\Template\Context;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Config\Block\System\Config\Form\Field;
 
class Editor extends Field
{
    public function __construct(
        Context $context,
        WysiwygConfig $wysiwygConfig,
        array $data = [])
   {
        $this->wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $data);
    }
 
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // set wysiwyg for element
        $element->setWysiwyg(true);
        // set configuration values
        $element->setConfig($this->wysiwygConfig->getConfig($element));
 
        return parent::_getElementHtml($element);
    }
}