<?php

namespace OnitsukaTiger\Store\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use OnitsukaTiger\Store\Block\Adminhtml\Form\Field\SourceColumn;
use OnitsukaTiger\Store\Block\Adminhtml\Form\Field\StoreColumn;

/**
 * Class Source
 */
class Source extends AbstractFieldArray
{
    /**
     * @var SourceColumn
     */
    private $sourceRenderer;

    /**
     * @var StoreColumn
     */
    private $storeRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('store', [
            'label' => __('Store View'),
            'renderer' => $this->getStoreRenderer(),
            'class' => 'required-entry',
        ]);
        $this->addColumn('source', [
            'label' => __('Source'),
            'renderer' => $this->getSourceRenderer(),
            'class' => 'required-entry',
            'extra_params' => 'multiple="multiple"'
        ]);

        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $store = $row->getStore();
        if ($store !== null) {
            $options['option_' . $this->getStoreRenderer()->calcOptionHash($store)] = 'selected="selected"';
        }

        $sources = $row->getSource();
        if (count($sources) > 0) {
            foreach ($sources as $source) {
                $options['option_' . $this->getSourceRenderer()->calcOptionHash($source)]
                    = 'selected="selected"';
            }
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return SourceColumn
     * @throws LocalizedException
     */
    private function getSourceRenderer()
    {
        if (!$this->sourceRenderer) {
            $this->sourceRenderer = $this->getLayout()->createBlock(
                SourceColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->sourceRenderer;
    }

    /**
     * @return StoreColumn
     * @throws LocalizedException
     */
    private function getStoreRenderer()
    {
        if (!$this->storeRenderer) {
            $this->storeRenderer = $this->getLayout()->createBlock(
                StoreColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->storeRenderer;
    }
}