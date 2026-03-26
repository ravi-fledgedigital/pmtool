<?php
declare(strict_types=1);

namespace OnitsukaTiger\Store\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Inventory\Model\ResourceModel\Source\Collection;

class SourceColumn extends Select
{
    /**
     * @var Collection
     */
    protected $sourceCollection;

    /**
     * @param Context $context
     * @param Collection $sourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Collection $sourceCollection,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sourceCollection = $sourceCollection;
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value . '[]');
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        $this->setExtraParams('multiple="multiple"');

        return parent::_toHtml();
    }

    private function getSourceOptions(): array
    {
        $sourceListArr = $this->sourceCollection->load();
        $data          = [];
        foreach ($sourceListArr as $sourceItemName) {
            $data[] = ['value' => $sourceItemName->getSourceCode(), 'label' => $sourceItemName->getName()];
        }

        return $data;
    }
}