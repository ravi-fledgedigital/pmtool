<?php

namespace OnitsukaTiger\SizeGuide\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class ProductGroupCmsMap extends AbstractFieldArray
{
    protected $cmsBlockRenderer;

    protected $productGroupRenderer;

    protected function _prepareToRender()
    {
        $this->addColumn('product_group', [
            'label' => __('Product Group'),
            'extra_params' => 'multiple="multiple"',
            'renderer' => $this->getProductGroupRenderer()
        ]);
        $this->addColumn('cms_block', [
            'label' => __('CMS Block'),
            'renderer' => $this->getCmsBlockRenderer()
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Mapping');
    }

    protected function getProductGroupRenderer()
    {
        if (!$this->productGroupRenderer) {
            $this->productGroupRenderer = $this->getLayout()->createBlock(
                \OnitsukaTiger\SizeGuide\Block\Adminhtml\System\Config\Form\Field\Renderer\ProductGroup::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->productGroupRenderer;
    }

    protected function getCmsBlockRenderer()
    {
        if (!$this->cmsBlockRenderer) {
            $this->cmsBlockRenderer = $this->getLayout()->createBlock(
                \OnitsukaTiger\SizeGuide\Block\Adminhtml\System\Config\Form\Field\Renderer\CmsBlock::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->cmsBlockRenderer;
    }

    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $options = [];

        $cmsBlock = $row->getData('cms_block');
        $productGroup = $row->getData('product_group');

        if ($cmsBlock) {
            $options['option_' . $this->getCmsBlockRenderer()->calcOptionHash($cmsBlock)] = 'selected="selected"';
        }
        if (count($productGroup) > 0) {
            foreach ($productGroup as $gp) {
                $options['option_' . $this->getProductGroupRenderer()->calcOptionHash($gp)]
                    = 'selected="selected"';
            }
        }

        $row->setData('option_extra_attrs', $options);
    }
}
