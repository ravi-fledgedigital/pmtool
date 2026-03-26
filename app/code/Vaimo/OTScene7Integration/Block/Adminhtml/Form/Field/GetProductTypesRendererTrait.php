<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Block\Adminhtml\Form\Field;

use Magento\Framework\DataObject;

trait GetProductTypesRendererTrait
{
    private ProductTypes $productTypes;

    private function getProductTypesRenderer(): ?ProductTypes
    {
        if (empty($this->productTypes)) {
            $this->productTypes = $this->getLayout()->createBlock(
                ProductTypes::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->productTypes;
    }

    protected function _prepareArrayRow(DataObject $row): void
    {
        $row->setData('option_extra_attrs', []);
    }
}
