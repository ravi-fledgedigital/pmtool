<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Block\Adminhtml\Config\Form\Field;

use Magento\Framework\Data\Form\Element\Obscure;

class PrivateKey extends Obscure
{
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function getElementHtml()
    {
        $this->addClass('textarea admin__control-textarea');
        $html = '<textarea id="' . $this->getHtmlId() . '" name="' . $this->getName() . '" '
            . $this->serialize($this->getHtmlAttributes()) . $this->_getUiId() . ' >';
        $html .= $this->getEscapedValue();
        $html .= '</textarea>';
        $html .= $this->getAfterElementHtml();

        return $html;
    }
}
