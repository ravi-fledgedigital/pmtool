<?php
namespace OnitsukaTiger\Catalog\Model\ConfigurableProduct;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Class Filter
 * @package OnitsukaTiger\Catalog\Model\ConfigurableProduct
 */
class Filter
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Filter constructor.
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param $configurableSku
     * @return \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection|\Magento\Catalog\Model\ResourceModel\Product\Collection|\Magento\Eav\Model\Entity\Collection\AbstractCollection
     */
    public function getChildProductsToAssign($configurableSku)
    {
        $productCollection = $this->collectionFactory->create();
        $productConfigurable = $productCollection->addAttributeToSelect('material_code')
            ->addFieldToFilter('sku', $configurableSku)
            ->getFirstItem();
        $styleCode = $productConfigurable->getMaterialCode();

        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('sku')
            ->addFieldToFilter('entity_id', ['neq' => $productConfigurable->getId()])
            ->addAttributeToFilter('type_id', \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
            ->addAttributeToFilter('material_code', $styleCode);
        return $collection->load();
    }
}
