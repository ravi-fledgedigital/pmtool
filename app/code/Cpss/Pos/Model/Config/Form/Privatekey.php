<?php

namespace Cpss\Pos\Model\Config\Form;

/**
 * Frontend model to obscure decrypted textarea
 *
 * Class Privatekey
 */
class Privatekey extends \Magento\Config\Block\System\Config\Form\Field
{
    const OBSCURED = '******';

    /**
     * Retrieve element HTML markup and add OBSCURED textarea value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($element->getValue()) {
            $element->setValue(self::OBSCURED);
        }
        return $element->getElementHtml();
    }
}
