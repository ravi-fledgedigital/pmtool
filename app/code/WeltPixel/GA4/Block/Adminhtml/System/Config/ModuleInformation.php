<?php
namespace WeltPixel\GA4\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;

/**
 * Class ModuleInformation
 * @package WeltPixel\GA4\Block\Adminhtml\System\Config
 */
class ModuleInformation extends Field
{
    protected $_template = 'WeltPixel_GA4::system/config/module_information.phtml';
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $elementData = $element->getOriginalData();
        $this->setData('element_data', $elementData);

        return $this->_toHtml();
    }
}
