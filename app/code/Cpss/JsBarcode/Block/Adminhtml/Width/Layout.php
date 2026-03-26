<?php

namespace Cpss\JsBarcode\Block\Adminhtml\Width;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Layout
 */
class Layout extends \Cpss\JsBarcode\Block\Adminhtml\Layout
{

    const FIELD_NAME = 'groups[barcode_group][fields][width][value]';

    /**
     * @var string
     */
    protected $_template = 'Cpss_JsBarcode::form/renderer/bar-width.phtml';

    /**
     * return field name
     * @param void
     * @return string
     */
    public function getFieldName()
    {
        return self::FIELD_NAME;
    }
}