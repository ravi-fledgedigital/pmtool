<?php


namespace OnitsukaTiger\NetSuite\Config\Backend;

class SourceMapping extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \OnitsukaTiger\NetSuite\Module\Block\Adminhtml\Form\Field\SourceColumn
     */
    private $sourceColumn;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('source', [
            'label' => __('Source'),
            'renderer' => $this->getSourceRenderer()
        ]);
        $this->addColumn('netsuite_id', ['label' => __('NetSuite ID'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param \Magento\Framework\DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row): void
    {
        $options = [];

        $tax = $row->getTax();
        if ($tax !== null) {
            $options['option_' . $this->getSourceRenderer()->calcOptionHash($tax)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return TaxColumn
     * @throws LocalizedException
     */
    private function getSourceRenderer()
    {
        if (!$this->sourceColumn) {
            $this->sourceColumn = $this->getLayout()->createBlock(
                \OnitsukaTiger\NetSuite\Module\Block\Adminhtml\Form\Field\SourceColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->sourceColumn;
    }
}
