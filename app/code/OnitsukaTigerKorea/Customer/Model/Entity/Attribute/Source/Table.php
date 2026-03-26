<?php
namespace OnitsukaTigerKorea\Customer\Model\Entity\Attribute\Source;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Store\Model\StoreManagerInterface;

class Table extends \Magento\Eav\Model\Entity\Attribute\Source\Table
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param CollectionFactory $attrOptionCollectionFactory
     * @param OptionFactory $attrOptionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $attrOptionCollectionFactory,
        OptionFactory $attrOptionFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($attrOptionCollectionFactory, $attrOptionFactory);
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve Full Option values array
     *
     * @param bool $withEmpty Add empty option to array
     * @param bool $defaultValues
     * @return array
     */
    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        $storeId = $this->getAttribute()->getStoreId() ?? $this->storeManager->getStore()->getId();

        $attributeId = $this->getAttribute()->getId();
        if (!isset($this->_options[$storeId][$attributeId])) {
            $collection = $this->_attrOptionCollectionFactory->create()
                ->setPositionOrder('asc')
                ->setAttributeFilter($attributeId)
                ->setStoreFilter($storeId)
                ->load();

            $this->_options[$storeId][$attributeId] = $collection->toOptionArray();
            $this->_optionsDefault[$storeId][$attributeId] = $collection->toOptionArray('default_value');
        }

        $options = $defaultValues
            ? $this->_optionsDefault[$storeId][$attributeId]
            : $this->_options[$storeId][$attributeId];

        if ($withEmpty) {
            $emptyLabel = $this->getAttribute()->getAttributeCode() === 'gender' ? 'Please Select' : ' ';
            $options = $this->addEmptyOption($options, $emptyLabel);
        }

        return $options;
    }

    /**
     * Add an empty option to the array
     *
     * @param array $options
     * @param string $emptyLabel
     * @return array
     */
    private function addEmptyOption(array $options, string $emptyLabel)
    {
        array_unshift($options, ['label' => $emptyLabel, 'value' => '']);
        return $options;
    }
}