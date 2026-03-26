<?php

namespace OnitsukaTiger\Reindex\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Class Data
 * @package OnitsukaTiger\Reindex\Helper
 */
class Data extends AbstractHelper
{
    const DIR = 'app';
    const FILEPATH = 'code/OnitsukaTiger/Reindex/Data/sku.xml';
    const FILENAME = 'sku.xml';

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CollectionFactory $productCollectionFactory
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->_productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getConfig($path, $storeId = null)
    {
        if ($storeId == null) {
            $storeId = $this->getStoreId();
        }
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get store identifier
     *
     * @return  int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @param $listSku
     * @return array
     */
    public function getProductIds($listSku) {
        $ids = [];
        $collection = $this->_productCollectionFactory->create();
        $collection->addFieldToFilter('sku', ['in' => $listSku]);
        if ($collection->getSize()) {
            foreach ($collection as $product) {
                if ($product->getTypeId() == 'configurable') {
                    $_childrens = $product->getTypeInstance()->getUsedProducts($product);
                    foreach ($_childrens as $children) {
                        $ids[] = $children->getID();
                    }
                }
                $ids[] = $product->getID();
            }
        }
        return array_unique($ids);
    }
}

