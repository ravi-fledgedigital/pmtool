<?php

namespace OnitsukaTiger\NetSuite\Module\Block\Adminhtml\Form\Field;

class SourceColumn extends \Magento\Framework\View\Element\Html\Select
{

    /**
     * @var \Magento\Inventory\Model\ResourceModel\Source\Collection
     */
    protected $sourceCollection;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Inventory\Model\ResourceModel\Source\Collection $sourceCollection,
        array $data = [],
    ) {
        $this->sourceCollection = $sourceCollection;
        parent::__construct($context, $data);
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
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
        return parent::_toHtml();
    }

    private function getSourceOptions(): array
    {
        $sourceListArr = $this->sourceCollection->load();
        $ret = [];
        foreach ($sourceListArr as $sourceItemName) {
            $ret[] = ['value' => $sourceItemName->getSourceCode(),  'label' => $sourceItemName->getName()];
        }
        return $ret;
    }
}
