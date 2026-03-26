<?php

namespace OnitsukaTigerKorea\CategoryFilters\Model;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var loadedData ;
     */
    protected $loadedData;

    /**
     * @var collection ;
     */
    protected $collection;

    /**
     * @var collectionFactory ;
     */
    protected $collectionFactory;

    /**
     * @var collectionFactoryRelationCategoryFilters ;
     */
    protected $collectionFactoryRelationCategoryFilters;

    /**
     * @var collectionRelationCategoryFilters ;
     */
    protected $collectionRelationCategoryFilters;

    /**
     * Generate dataprovider
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ..\ResourceModel\RelationCategoryFilters\Collection $collectionRelationCategoryFilters
     * @param ..\Model\RelationCategoryFiltersFactory $collectionFactoryRelationCategoryFilters
     * @param ..\Model\ResourceModel\CategoryFilters\Collection $collection
     * @param ..\Model\ResourceModel\CategoryFilters\CollectionFactory $collectionFactory
     * @param array $meta = []
     * @param array $data = []
     * @return string
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \OnitsukaTigerKorea\CategoryFilters\Model\ResourceModel\RelationCategoryFilters\Collection
        $collectionRelationCategoryFilters,
        \OnitsukaTigerKorea\CategoryFilters\Model\RelationCategoryFiltersFactory
        $collectionFactoryRelationCategoryFilters,
        \OnitsukaTigerKorea\CategoryFilters\Model\ResourceModel\CategoryFilters\Collection
        $collection,
        \OnitsukaTigerKorea\CategoryFilters\Model\ResourceModel\CategoryFilters\CollectionFactory
        $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collectionRelationCategoryFilters = $collectionRelationCategoryFilters;
        $this->collectionFactoryRelationCategoryFilters = $collectionFactoryRelationCategoryFilters;
        $this->collection = $collection;
        $this->collectionFactory = $collectionFactory;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
    }

    /**
     * Get Data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $collection = $this->collectionFactory->create();

        $items = $collection->getItems();

        foreach ($items as $item) {
            $this->loadedData[$item->getId()] = $item->getData();
            $collection = $this->collectionFactoryRelationCategoryFilters
                ->create()
                ->getCollection();
            $collection->addFieldToFilter("filter_id", [
                "eq" => $item->getId(),
            ]);
            if ($collection && $collection->getSize() > 0) {
                $dynamic_data = [];
                $i = 0;
                foreach ($collection as $itemsCollection) {
                    $testArray = $itemsCollection->getData();
                    $testArray["record_id"] = $i;
                    $testArray["position"] = $i;
                    $dynamic_data[] = $testArray;
                    $this->loadedData[$item->getId()][
                        "dynamic_row"
                    ][] = $testArray;
                    $i++;
                }
            }
        }
        return $this->loadedData;
    }
}
