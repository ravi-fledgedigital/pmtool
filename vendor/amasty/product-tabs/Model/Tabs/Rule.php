<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs;

use Amasty\CustomTabs\Model\Tabs\Condition\SqlBuilder;
use Magento\Framework\App\ObjectManager;

class Rule extends \Magento\CatalogRule\Model\Rule
{
    /**
     * @inheritdoc
     */
    public function getMatchingProductIdsByTab()
    {
        if ($this->_productIds === null) {
            $this->_productIds = [];
            $this->setCollectedAttributes([]);

            $stores = $this->getStores();
            if (in_array(0, $stores)) {
                $stores = array_keys($this->_storeManager->getStores());
            }

            foreach ($stores as $storeId) {
                /** @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
                $productCollection = $this->_productCollectionFactory->create()
                    ->setStoreId($storeId);

                if ($this->_productsFilter) {
                    $productCollection->addIdFilter($this->_productsFilter);
                }

                $sqlBuilder = $this->createSqlBuilderObject();
                $sqlBuilder->attachConditionToCollection(
                    $productCollection,
                    $this->getConditions()->collectValidatedAttributes($productCollection)
                );
                foreach ($productCollection->getAllIds() as $productId) {
                    $this->_productIds[$productId][] = $storeId;
                }
            }
        }

        return $this->_productIds;
    }

    /**
     * @deprecated validation logic is improved.
     * @see \Amasty\CustomTabs\Model\Tabs\Condition\SqlBuilder
     */
    public function callbackValidateProduct($args)
    {
        $storeId = $args['store_id'];
        $product = $args['product'];
        $product->setData($args['row']);
        $product->setStoreId($storeId);

        if ($this->getConditions()->validate($product)) {
            $this->_productIds[$product->getId()][] = $storeId;
        }
    }

    // Use OM instead of initialization in __constructor() method with a large number of arguments.
    private function createSqlBuilderObject(): SqlBuilder
    {
        return ObjectManager::getInstance()->get(SqlBuilder::class);
    }
}
