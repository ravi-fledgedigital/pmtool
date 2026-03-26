<?php

namespace OnitsukaTiger\Reindex\Block\Adminhtml\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Import
 * @package OnitsukaTiger\Reindex\Block\Adminhtml\Form\Field
 */
class Import extends AbstractElement
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setType('file');
    }

    /**
     * Create empty block
     *
     * @return string
     */
    public function getElementHtml()
    {
        $html = '';

        $html .= parent::getElementHtml();

        return $html;
    }
}
