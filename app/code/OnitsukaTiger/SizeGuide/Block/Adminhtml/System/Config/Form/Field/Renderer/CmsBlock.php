<?php

namespace OnitsukaTiger\SizeGuide\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Html\Select;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;

class CmsBlock extends Select
{
    protected $cmsBlockFactory;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        CollectionFactory $cmsBlockFactory,
        array $data = []
    ) {
        $this->cmsBlockFactory = $cmsBlockFactory;
        parent::__construct($context, $data);
    }

    public function setInputName($value)
    {
        return $this->setName($value);
    }

    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            $collection = $this->cmsBlockFactory->create();
            foreach ($collection as $block) {
                $this->addOption($block->getIdentifier(), $block->getTitle());
            }
        }
        return parent::_toHtml();
    }
}
