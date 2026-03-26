<?php

declare(strict_types=1);

namespace OnitsukaTiger\Cegid\Block\Adminhtml\Form\Field;
use Magento\Framework\Exception\LocalizedException;
use OnitsukaTiger\Cegid\Block\Adminhtml\Form\Field\SourceCodeTypes;

use Magento\Framework\DataObject;

trait GetSourceTypesRendererTrait
{
    private SourceCodeTypes $sourceCodeTypes;

    /**
     * @return SourceCodeTypes|null
     * @throws LocalizedException
     */
    private function getProductTypesRenderer(): ?SourceCodeTypes
    {
        if (empty($this->sourceCodeTypes)) {
            $this->sourceCodeTypes = $this->getLayout()->createBlock(
                SourceCodeTypes::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->sourceCodeTypes;
    }

    /**
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $row->setData('option_extra_attrs', []);
    }
}
