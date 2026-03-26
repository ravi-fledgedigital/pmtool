<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class ProductTypes extends Select
{
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }

        return parent::_toHtml();
    }

    public function setInputId(string $value): ProductTypes
    {
        return $this->setId($value);
    }

    public function setInputName(string $value): ProductTypes
    {
        return $this->setData('name', $value);
    }

    /**
     * @return string[][]
     */
    private function getSourceOptions(): array
    {
        return [
            ['value' => 'Footwear', 'label' => 'Footwear'],
            ['value' => 'Apparel', 'label' => 'Apparel'],
            ['value' => 'Accessories and Equipment', 'label' => 'Accessories and Equipment'],
        ];
    }
}
