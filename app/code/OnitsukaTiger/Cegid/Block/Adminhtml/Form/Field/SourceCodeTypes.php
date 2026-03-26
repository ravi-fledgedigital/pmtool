<?php


declare(strict_types=1);

namespace OnitsukaTiger\Cegid\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Inventory\Model\ResourceModel\Source\Collection;

class SourceCodeTypes extends Select
{
    private Collection $collection;

    /**
     * @param Collection $collection
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Collection $collection,
        Context $context,
        array $data = []
    ) {
        $this->collection = $collection;
        parent::__construct($context, $data);
    }

    /**
     * Get html
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }

        return parent::_toHtml();
    }

    /**
     * Set input ID
     * @param string $value
     * @return SourceCodeTypes
     */
    public function setInputId(string $value): SourceCodeTypes
    {
        return $this->setId($value);
    }

    /**
     * Set input name
     * @param string $value
     * @return SourceCodeTypes
     */
    public function setInputName(string $value): SourceCodeTypes
    {
        return $this->setData('name', $value);
    }
    /**
     * Get source option value
     * @return string[][]
     */
    private function getSourceOptions(): array
    {
        $data = [];
        foreach ($this->collection->getItems() as $item) {
            $data[$item->getSourceCode()] = $item->getSourceCode();
        }
        return $data;
    }
}
