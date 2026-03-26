<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTigerKorea\CategoryFilters\Ui\Component\Product\Form\Categories;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Category as CategoryModel;

/**
 * Options tree for "Categories" field
 */
class Options implements OptionSourceInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    protected $categoryTreeId;

    /**
     * @var $categoriesTree
     */
    protected $categoriesTree;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param \OnitsukaTigerKorea\CategoryFilters\Helper\Data $categoryTreeId
     * @param RequestInterface $request
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        \OnitsukaTigerKorea\CategoryFilters\Helper\Data $categoryTreeId,
        RequestInterface $request
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryTreeId = $categoryTreeId;
        $this->request = $request;
    }

    /**
     * Get categories tree
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getCategoriesTree();
    }

    /**
     * Retrieve categories tree
     *
     * @return array
     */
    protected function getCategoriesTree()
    {
        if ($this->categoriesTree === null) {
            $storeId = 5;
            /* @var $matchingNamesCollection \Magento\Catalog\Model\ResourceModel\Category\Collection */
            $matchingNamesCollection = $this->categoryCollectionFactory->create();

            $matchingNamesCollection
                ->addAttributeToSelect("path")
                ->addAttributeToFilter("entity_id", [
                    "neq" => CategoryModel::TREE_ROOT_ID,
                ])
                ->setStoreId($storeId);

            $shownCategoriesIds = [];

            /** @var \Magento\Catalog\Model\Category $category */
            foreach ($matchingNamesCollection as $category) {
                $explodeCategory = explode("/", $category->getPath());
                $categoryTreeId = $this->categoryTreeId->getCategoryTreeId();
                if (!in_array($categoryTreeId, $explodeCategory)) {
                    continue;
                }
                foreach (explode("/", $category->getPath()) as $parentId) {
                    $shownCategoriesIds[$parentId] = 1;
                }
            }

            /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
            $collection = $this->categoryCollectionFactory->create();

            $collection
                ->addAttributeToFilter("entity_id", [
                    "in" => array_keys($shownCategoriesIds),
                ])
                ->addAttributeToSelect(["name", "is_active", "parent_id"])
                ->setStoreId($storeId);

            $categoryById = [
                CategoryModel::TREE_ROOT_ID => [
                    "value" => CategoryModel::TREE_ROOT_ID,
                ],
            ];

            foreach ($collection as $category) {
                foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                    if (!isset($categoryById[$categoryId])) {
                        $categoryById[$categoryId] = ["value" => $categoryId];
                    }
                }

                $categoryById[$category->getId()][
                    "is_active"
                ] = $category->getIsActive();
                $categoryById[$category->getId()][
                    "label"
                ] = $category->getName();
                $categoryById[$category->getParentId()]["optgroup"][] =
                    &$categoryById[$category->getId()];
            }

            $this->categoriesTree = $categoryById[CategoryModel::TREE_ROOT_ID]["optgroup"];
        }

        return $this->categoriesTree;
    }
}
