<?php
//phpcs:ignoreFile
namespace Cpss\Crm\Model\Source;

class CountryOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $countryOptions = [];
        $collections = $this->collectionFactory->create()->toOptionArray();
        if (!empty($collections)) {
            foreach ($collections as $country) {
                $countryOptions[] = ['value' => strtolower($country['value']), 'label' => __($country['label'])];
            }
        }
        return $countryOptions;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $countryOptions = [];
        $collections = $this->collectionFactory->create()->toOptionArray();
        if (!empty($collections)) {
            foreach ($collections as $country) {
                $countryOptions[strtolower($country['value'])] = $country['label'];
            }
        }
        return $countryOptions;
    }
}
