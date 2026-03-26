<?php

namespace OnitsukaTiger\Catalog\Model\Tabs;

use Magento\CatalogRule\Model\Rule as CatalogRule;

class Rule extends \Amasty\CustomTabs\Model\Tabs\Rule
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

                $this->getConditions()->collectValidatedAttributes($productCollection);

                $this->_resourceIterator->walk(
                    $productCollection->getSelect(),
                    [[$this, 'callbackValidateProduct']],
                    [
                        'attributes' => $this->getCollectedAttributes(),
                        'product' => $this->_productFactory->create(),
                        'store_id' => $storeId
                    ]
                );
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
