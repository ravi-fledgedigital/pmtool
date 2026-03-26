<?php

namespace OnitsukaTiger\Catalog\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Widget\Helper\Conditions;

class AfterCreateCollection
{
    /**
     * @var Conditions
     */
    protected $conditions;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Conditions $conditions
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Conditions $conditions,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->conditions = $conditions;
        $this->storeManager = $storeManager;
    }
    public function aftercreateCollection($subject, $result)
    {
        $store = $this->storeManager->getStore()->getCode();
        if($subject->getType() == "Magento\CatalogWidget\Block\Product\ProductsList" && $store == 'web_kr_ko') {
            /**
             * @var Collection $result
             * @var ProductsList $subject
             */
            $skus = [];
            if(!empty($subject->getConditionsEncoded())) {
                $conditions = $this->decodeConditions($subject->getConditionsEncoded());
                foreach ($conditions as $key => $condition) {
                    if (!empty($condition['attribute'])) {
                        if (in_array($condition['attribute'], ['sku'])) {
                            $skus[] = $conditions[$key]['value'];
                        }
                    }
                }
                $result->getSelect()->order(
                    new \Zend_Db_Expr(
                        'FIELD(e.sku, "' . implode('","', $skus) . '")'
                    )
                );
            }
        }
        return $result;
    }

    /**
     * @param string $encodedConditions
     * @return array
     */

    private function decodeConditions(string $encodedConditions): array
    {
        return $this->conditions->decode(htmlspecialchars_decode($encodedConditions));
    }
}