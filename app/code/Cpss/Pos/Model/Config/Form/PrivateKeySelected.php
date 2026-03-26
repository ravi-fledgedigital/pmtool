<?php

namespace Cpss\Pos\Model\Config\Form;

/**
 * Frontend model to set private key type in case private key already saved prior to the file/text feature
 *
 * Class PrivateKeySelected
 */
class PrivateKeySelected extends \Magento\Config\Block\System\Config\Form\Field
{
    const TEXT_VALUE = 'text';

    /**
     * Retrieve element HTML markup and add OBSCURED textarea value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $group = '';

        switch ($element->getId()) {
            case 'sftp_pos_private_key_selected':
                $group = 'pos';
                break;
            case 'sftp_cpss_private_key_selected':
                $group = 'cpss';
                break;
            case 'sftp_real_store_private_key_selected':
                $group = 'real_store';
                break;
        }

        if (empty($element->getValue()) &&
            $this->_scopeConfig->getValue('sftp/' . $group . '/private_key')
        ) {
            $element->setValue(self::TEXT_VALUE);
        }

        return $element->getElementHtml();
    }
}
