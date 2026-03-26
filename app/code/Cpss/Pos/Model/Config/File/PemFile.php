<?php

namespace Cpss\Pos\Model\Config\File;

class PemFile extends \Magento\Config\Block\System\Config\Form\Field\File
{
    /**
     * @return string
     */
    protected function _getDeleteCheckbox()
    {
        if ($this->getValue()) {
            return '<div id="note_private_key_pem_file_saved_msg"><strong>Key file already saved</strong></div><br>';
        }

        return '';
    }
}
