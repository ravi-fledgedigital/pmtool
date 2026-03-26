<?php

namespace OnitsukaTigerIndo\SizeConverter\Model\Source;

class EnglishSizeOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    public function __construct(
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $optionCollection = $this->getQaSizeOptions();
        $qaSizeOptions = [];
        foreach ($optionCollection as $sizeOptions) {
            $qaSizeOptions[] = ['value' => $sizeOptions['value'], 'label' => $sizeOptions['label']];
        }
        return $qaSizeOptions;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $optionCollection = $this->getQaSizeOptions();
        $qaSizeOptions = [];
        foreach ($optionCollection as $sizeOptions) {
            $qaSizeOptions[$sizeOptions['value']] = $sizeOptions['label'];
        }
        return $qaSizeOptions;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getQaSizeOptions()
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', 'qa_size');
        return $attribute->getSource()->getAllOptions();
    }
}
