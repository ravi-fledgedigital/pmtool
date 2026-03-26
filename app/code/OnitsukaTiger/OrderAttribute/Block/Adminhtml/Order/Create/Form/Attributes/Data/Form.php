<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
namespace OnitsukaTiger\OrderAttribute\Block\Adminhtml\Order\Create\Form\Attributes\Data;

use Magento\Framework\Data\Form as FrameworkForm;

class Form extends FrameworkForm
{
    /**
     * Escape suffix for file input
     *
     * @param string $suffix
     * @return $this
     */
    public function addFieldNameSuffix($suffix)
    {
        foreach ($this->_allElements as $element) {
            if ($element->getType() === 'file') {
                continue;
            }
            $name = $element->getName();
            if ($name) {
                $element->setName($this->addSuffixToName($name, $suffix));
            }
        }
        return $this;
    }
}
